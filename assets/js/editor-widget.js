/**
 * RDS AI Excerpt Generator - Gutenberg Editor Widget
 */

(function (wp) {
  "use strict";

  // Check if wp.plugins exists
  if (!wp.plugins) {
    console.error("wp.plugins is not available");
    return;
  }

  // Destructure all needed components
  const {
    plugins: { registerPlugin },
    editPost: { PluginDocumentSettingPanel },
    element: { createElement: wpCreateElement, useState, useEffect },
    components: {
      SelectControl,
      TextControl,
      TextareaControl,
      Button,
      Spinner,
      Notice,
      __experimentalNumberControl: ExperimentalNumberControl,
    },
    data: { useDispatch, useSelect },
    i18n: { __ },
    apiFetch,
  } = wp;

  // Use wp.element.createElement
  const createElement = wpCreateElement;

  // Check for NumberControl
  let NumberControl = ExperimentalNumberControl;
  if (!NumberControl && wp.components.NumberControl) {
    NumberControl = wp.components.NumberControl;
  }
  if (!NumberControl) {
    // Fallback if NumberControl doesn't exist
    NumberControl = function (props) {
      return createElement("input", {
        type: "number",
        className: "components-text-control__input",
        value: props.value,
        onChange: function (e) {
          props.onChange(parseInt(e.target.value, 10));
        },
        min: props.min,
        max: props.max,
        step: props.step,
      });
    };
  }

  // Get localized data
  const widgetData = window.rdsAIExcerptWidget || {};

  /**
   * Main Component
   */
  const AIExcerptPanel = function () {
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState("");
    const [success, setSuccess] = useState("");
    const [excerpt, setExcerpt] = useState("");
    const [params, setParams] = useState({
      style: widgetData.defaults?.style || "descriptive",
      tone: widgetData.defaults?.tone || "neutral",
      language: widgetData.defaults?.language || "en",
      max_length: widgetData.defaults?.maxLength || 150,
      focus_keywords: widgetData.defaults?.focusKeywords || "",
    });

    const { editPost } = useDispatch("core/editor");

    // Get current excerpt from editor state
    const currentExcerpt = useSelect(function (select) {
      return select("core/editor").getEditedPostAttribute("excerpt") || "";
    }, []);

    const postId = useSelect(function (select) {
      return select("core/editor").getCurrentPostId();
    }, []);

    /**
     * Handle parameter change
     */
    const handleParamChange = function (key, value) {
      setParams(function (prev) {
        var newParams = Object.assign({}, prev);
        newParams[key] = value;
        return newParams;
      });
      // Clear messages
      setError("");
      setSuccess("");
    };

    /**
     * Generate excerpt using AI
     */
    const generateExcerpt = async function () {
      setLoading(true);
      setError("");
      setSuccess("");
      setExcerpt("");

      try {
        console.log("Generating excerpt with params:", params);
        console.log("Post ID:", postId);
        console.log("Nonce:", widgetData.nonce);

        // Используем FormData для правильной отправки данных
        const formData = new FormData();
        formData.append("action", "rds_ai_generate_excerpt");
        formData.append("nonce", widgetData.nonce);
        formData.append("post_id", postId);
        formData.append("style", params.style);
        formData.append("tone", params.tone);
        formData.append("language", params.language);
        formData.append("max_length", params.max_length);
        formData.append("focus_keywords", params.focus_keywords);

        const response = await apiFetch({
          url: widgetData.ajaxUrl || "/wp-admin/admin-ajax.php",
          method: "POST",
          body: formData,
        });

        console.log("AI Generation Response:", response);

        if (response.success) {
          setExcerpt(response.excerpt || "");
          setSuccess(
            widgetData.strings?.success ||
              __("Excerpt generated successfully!", "rds-ai-excerpt")
          );
        } else {
          setError(
            response.error || __("Unknown error occurred.", "rds-ai-excerpt")
          );
        }
      } catch (err) {
        console.error("AI Generation Error:", err);
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
    const applyExcerpt = function () {
      if (!excerpt || excerpt.trim() === "") {
        setError(__("No excerpt to apply.", "rds-ai-excerpt"));
        return;
      }

      try {
        // Update excerpt through editor API
        editPost({ excerpt: excerpt.trim() });

        // Show success message
        setSuccess(
          widgetData.strings?.applied ||
            __("Excerpt applied successfully!", "rds-ai-excerpt")
        );

        // Clear success after 3 seconds
        setTimeout(function () {
          setSuccess("");
        }, 3000);

        console.log("Excerpt applied:", excerpt);
      } catch (err) {
        console.error("Failed to apply excerpt:", err);
        setError(__("Failed to apply excerpt to post.", "rds-ai-excerpt"));
      }
    };

    /**
     * Copy excerpt to clipboard
     */
    const copyExcerpt = async function () {
      if (!excerpt || excerpt.trim() === "") {
        setError(__("No excerpt to copy.", "rds-ai-excerpt"));
        return;
      }

      try {
        await navigator.clipboard.writeText(excerpt.trim());
        setSuccess(
          widgetData.strings?.copied ||
            __("Copied to clipboard!", "rds-ai-excerpt")
        );

        // Clear success after 3 seconds
        setTimeout(function () {
          setSuccess("");
        }, 3000);
      } catch (err) {
        console.error("Copy failed:", err);
        setError(__("Failed to copy to clipboard.", "rds-ai-excerpt"));
      }
    };

    // Convert widgetData to options arrays
    const styleOptions = [
      {
        label:
          widgetData.styles?.descriptive || __("Descriptive", "rds-ai-excerpt"),
        value: "descriptive",
      },
      {
        label:
          widgetData.styles?.advertising || __("Advertising", "rds-ai-excerpt"),
        value: "advertising",
      },
      {
        label: widgetData.styles?.business || __("Business", "rds-ai-excerpt"),
        value: "business",
      },
      {
        label: widgetData.styles?.creative || __("Creative", "rds-ai-excerpt"),
        value: "creative",
      },
    ];

    const toneOptions = [
      {
        label: widgetData.tones?.neutral || __("Neutral", "rds-ai-excerpt"),
        value: "neutral",
      },
      {
        label: widgetData.tones?.formal || __("Formal", "rds-ai-excerpt"),
        value: "formal",
      },
      {
        label: widgetData.tones?.friendly || __("Friendly", "rds-ai-excerpt"),
        value: "friendly",
      },
      {
        label:
          widgetData.tones?.professional ||
          __("Professional", "rds-ai-excerpt"),
        value: "professional",
      },
    ];

    const languageOptions = Object.entries(
      widgetData.languages || { en: "English" }
    ).map(function ([value, label]) {
      return { label: label, value: value };
    });

    // Use React.createElement instead of JSX
    return createElement(
      PluginDocumentSettingPanel,
      {
        name: "rds-ai-excerpt-panel",
        title:
          widgetData.strings?.title ||
          __("AI Excerpt Generator", "rds-ai-excerpt"),
        className: "rds-ai-excerpt-widget",
      },
      // Current Excerpt Preview
      currentExcerpt &&
        createElement(
          "div",
          {
            style: {
              marginBottom: "20px",
              padding: "10px",
              background: "#f0f0f1",
              borderRadius: "4px",
            },
          },
          createElement(
            "p",
            {
              style: { marginTop: 0, marginBottom: "5px", fontWeight: "bold" },
            },
            __("Current Excerpt:", "rds-ai-excerpt")
          ),
          createElement(
            "p",
            { style: { margin: 0, fontSize: "13px", color: "#50575e" } },
            currentExcerpt.length > 100
              ? currentExcerpt.substring(0, 100) + "..."
              : currentExcerpt
          )
        ),

      // Error Notice
      error &&
        createElement(
          Notice,
          {
            status: "error",
            isDismissible: true,
            onRemove: function () {
              setError("");
            },
            style: { marginBottom: "16px" },
          },
          createElement(
            "strong",
            null,
            widgetData.strings?.error || __("Error:", "rds-ai-excerpt")
          ),
          " ",
          error
        ),

      // Success Notice
      success &&
        createElement(
          Notice,
          {
            status: "success",
            isDismissible: true,
            onRemove: function () {
              setSuccess("");
            },
            style: { marginBottom: "16px" },
          },
          success
        ),

      // Loading Indicator
      loading &&
        createElement(
          "div",
          { className: "generating-indicator" },
          createElement(Spinner),
          createElement(
            "span",
            null,
            widgetData.strings?.generating ||
              __("Generating excerpt...", "rds-ai-excerpt")
          )
        ),

      // Style Select
      createElement(SelectControl, {
        label: widgetData.strings?.style || __("Style:", "rds-ai-excerpt"),
        value: params.style,
        options: styleOptions,
        onChange: function (value) {
          handleParamChange("style", value);
        },
      }),

      // Tone Select
      createElement(SelectControl, {
        label: widgetData.strings?.tone || __("Tone:", "rds-ai-excerpt"),
        value: params.tone,
        options: toneOptions,
        onChange: function (value) {
          handleParamChange("tone", value);
        },
      }),

      // Language Select
      createElement(SelectControl, {
        label:
          widgetData.strings?.language || __("Language:", "rds-ai-excerpt"),
        value: params.language,
        options: languageOptions,
        onChange: function (value) {
          handleParamChange("language", value);
        },
      }),

      // Max Length Number Control
      createElement(NumberControl, {
        label:
          widgetData.strings?.maxLength ||
          __("Max Length (words):", "rds-ai-excerpt"),
        value: params.max_length,
        min: 50,
        max: 500,
        step: 10,
        onChange: function (value) {
          handleParamChange("max_length", value);
        },
      }),

      // Focus Keywords Text Control
      createElement(TextControl, {
        label:
          widgetData.strings?.focusKeywords ||
          __("Focus Keywords:", "rds-ai-excerpt"),
        value: params.focus_keywords,
        placeholder: "keyword1, keyword2, keyword3",
        onChange: function (value) {
          handleParamChange("focus_keywords", value);
        },
      }),

      // Generate Button
      createElement(
        Button,
        {
          isPrimary: true,
          onClick: generateExcerpt,
          disabled: loading,
          style: { marginBottom: "16px" },
        },
        widgetData.strings?.generate || __("Generate Excerpt", "rds-ai-excerpt")
      ),

      // Result Area
      excerpt &&
        createElement(
          "div",
          {
            className: "result-area",
            style: {
              marginTop: "24px",
              paddingTop: "24px",
              borderTop: "1px solid #e0e0e0",
            },
          },
          createElement(TextareaControl, {
            label:
              widgetData.strings?.generatedExcerpt ||
              __("Generated Excerpt:", "rds-ai-excerpt"),
            value: excerpt,
            readOnly: true,
            rows: 4,
            style: { marginBottom: "16px" },
          }),

          createElement(
            "div",
            {
              className: "result-actions",
              style: { display: "flex", gap: "8px" },
            },
            createElement(
              Button,
              {
                isSecondary: true,
                onClick: applyExcerpt,
                style: { flex: 1 },
              },
              widgetData.strings?.apply ||
                __("Apply to Excerpt", "rds-ai-excerpt")
            ),

            createElement(
              Button,
              {
                onClick: copyExcerpt,
                style: { flex: 1 },
              },
              widgetData.strings?.copy || __("Copy", "rds-ai-excerpt")
            )
          )
        )
    );
  };

  // Register the plugin
  try {
    registerPlugin("rds-ai-excerpt-generator", {
      render: AIExcerptPanel,
      icon: "text",
    });
    console.log("RDS AI Excerpt plugin registered successfully");
  } catch (err) {
    console.error("Failed to register RDS AI Excerpt plugin:", err);
  }
})(window.wp);
