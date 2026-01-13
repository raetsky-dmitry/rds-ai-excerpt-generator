/**
 * RDS AI Excerpt Generator - Gutenberg Editor Widget
 */

(function (wp) {
  const { registerPlugin } = wp.plugins;
  const { PluginDocumentSettingPanel } = wp.editPost;
  const { useState } = wp.element;
  const {
    SelectControl,
    TextControl,
    TextareaControl,
    Button,
    Spinner,
    Notice,
    __experimentalNumberControl: NumberControl,
  } = wp.components;
  const { useDispatch, useSelect } = wp.data;
  const { __ } = wp.i18n;

  // Get localized data
  const widgetData = window.rdsAIExcerptWidget || {};

  /**
   * Main Component
   */
  const AIExcerptPanel = () => {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");
    const [excerpt, setExcerpt] = useState("");
    const [params, setParams] = useState({
      style: widgetData.defaults.style || "descriptive",
      tone: widgetData.defaults.tone || "neutral",
      language: widgetData.defaults.language || "en",
      max_length: widgetData.defaults.maxLength || 150,
      focus_keywords: widgetData.defaults.focusKeywords || "",
    });

    const { editPost } = useDispatch("core/editor");
    const postId = useSelect(
      (select) => select("core/editor").getCurrentPostId(),
      []
    );

    /**
     * Handle parameter change
     */
    const handleParamChange = (key, value) => {
      setParams((prev) => ({
        ...prev,
        [key]: value,
      }));
      // Clear messages
      setError("");
      setSuccess("");
    };

    /**
     * Generate excerpt using AI
     */
    const generateExcerpt = async () => {
      setLoading(true);
      setError("");
      setSuccess("");
      setExcerpt("");

      try {
        const response = await wp.apiFetch({
          path: "/wp-admin/admin-ajax.php",
          method: "POST",
          data: {
            action: "rds_ai_generate_excerpt",
            nonce: widgetData.nonce,
            post_id: postId,
            ...params,
          },
        });

        if (response.success) {
          setExcerpt(response.excerpt);
          setSuccess(
            widgetData.strings.success ||
              __("Excerpt generated successfully!", "rds-ai-excerpt")
          );
        } else {
          setError(
            response.error || __("Unknown error occurred.", "rds-ai-excerpt")
          );
        }
      } catch (err) {
        setError(
          err.message ||
            __(
              "Failed to generate excerpt. Please try again.",
              "rds-ai-excerpt"
            )
        );
      } finally {
        setLoading(false);
      }
    };

    /**
     * Apply excerpt to post
     */
    const applyExcerpt = () => {
      if (!excerpt) {
        return;
      }

      editPost({ excerpt });
      setSuccess(
        widgetData.strings.applied ||
          __("Excerpt applied successfully!", "rds-ai-excerpt")
      );
    };

    /**
     * Copy excerpt to clipboard
     */
    const copyExcerpt = async () => {
      if (!excerpt) {
        return;
      }

      try {
        await navigator.clipboard.writeText(excerpt);
        setSuccess(
          widgetData.strings.copied ||
            __("Copied to clipboard!", "rds-ai-excerpt")
        );
      } catch (err) {
        setError(__("Failed to copy to clipboard.", "rds-ai-excerpt"));
      }
    };

    return (
      <PluginDocumentSettingPanel
        name="rds-ai-excerpt-panel"
        title={
          widgetData.strings.title ||
          __("AI Excerpt Generator", "rds-ai-excerpt")
        }
        className="rds-ai-excerpt-widget">
        {error && (
          <Notice status="error" isDismissible={false}>
            <strong>
              {widgetData.strings.error || __("Error:", "rds-ai-excerpt")}
            </strong>{" "}
            {error}
          </Notice>
        )}

        {success && (
          <Notice status="success" isDismissible={false}>
            {success}
          </Notice>
        )}

        {loading && (
          <div className="generating-indicator">
            <Spinner />
            <span>
              {widgetData.strings.generating ||
                __("Generating excerpt...", "rds-ai-excerpt")}
            </span>
          </div>
        )}

        <SelectControl
          label={widgetData.strings.style || __("Style:", "rds-ai-excerpt")}
          value={params.style}
          options={[
            {
              label:
                widgetData.styles?.descriptive ||
                __("Descriptive", "rds-ai-excerpt"),
              value: "descriptive",
            },
            {
              label:
                widgetData.styles?.advertising ||
                __("Advertising", "rds-ai-excerpt"),
              value: "advertising",
            },
            {
              label:
                widgetData.styles?.business || __("Business", "rds-ai-excerpt"),
              value: "business",
            },
            {
              label:
                widgetData.styles?.creative || __("Creative", "rds-ai-excerpt"),
              value: "creative",
            },
          ]}
          onChange={(value) => handleParamChange("style", value)}
        />

        <SelectControl
          label={widgetData.strings.tone || __("Tone:", "rds-ai-excerpt")}
          value={params.tone}
          options={[
            {
              label:
                widgetData.tones?.neutral || __("Neutral", "rds-ai-excerpt"),
              value: "neutral",
            },
            {
              label: widgetData.tones?.formal || __("Formal", "rds-ai-excerpt"),
              value: "formal",
            },
            {
              label:
                widgetData.tones?.friendly || __("Friendly", "rds-ai-excerpt"),
              value: "friendly",
            },
            {
              label:
                widgetData.tones?.professional ||
                __("Professional", "rds-ai-excerpt"),
              value: "professional",
            },
          ]}
          onChange={(value) => handleParamChange("tone", value)}
        />

        <SelectControl
          label={
            widgetData.strings.language || __("Language:", "rds-ai-excerpt")
          }
          value={params.language}
          options={Object.entries(widgetData.languages || {}).map(
            ([value, label]) => ({
              label,
              value,
            })
          )}
          onChange={(value) => handleParamChange("language", value)}
        />

        <NumberControl
          label={
            widgetData.strings.maxLength ||
            __("Max Length (words):", "rds-ai-excerpt")
          }
          value={params.max_length}
          min={50}
          max={500}
          step={10}
          onChange={(value) => handleParamChange("max_length", value)}
        />

        <TextControl
          label={
            widgetData.strings.focusKeywords ||
            __("Focus Keywords:", "rds-ai-excerpt")
          }
          value={params.focus_keywords}
          placeholder="keyword1, keyword2, keyword3"
          onChange={(value) => handleParamChange("focus_keywords", value)}
        />

        <Button isPrimary onClick={generateExcerpt} disabled={loading}>
          {widgetData.strings.generate ||
            __("Generate Excerpt", "rds-ai-excerpt")}
        </Button>

        {excerpt && (
          <div className="result-area">
            <TextareaControl
              label={
                widgetData.strings.generatedExcerpt ||
                __("Generated Excerpt:", "rds-ai-excerpt")
              }
              value={excerpt}
              readOnly
              rows={4}
            />

            <div className="result-actions">
              <Button isSecondary onClick={applyExcerpt}>
                {widgetData.strings.apply ||
                  __("Apply to Excerpt", "rds-ai-excerpt")}
              </Button>

              <Button onClick={copyExcerpt}>
                {widgetData.strings.copy || __("Copy", "rds-ai-excerpt")}
              </Button>
            </div>
          </div>
        )}
      </PluginDocumentSettingPanel>
    );
  };

  // Register the plugin
  registerPlugin("rds-ai-excerpt-generator", {
    render: AIExcerptPanel,
    icon: "text",
  });
})(window.wp);
