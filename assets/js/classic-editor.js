/**
 * RDS AI Excerpt Generator - Classic Editor Widget
 */

(function ($) {
  "use strict";

  /**
   * Main Widget Class
   */
  function RDSAIExcerptWidget() {
    var self = this; // Сохраняем контекст

    /**
     * Check if we're in Gutenberg editor
     */
    this.isGutenbergEditor = function () {
      // Способ 1: Проверяем по классам body
      if ($("body").hasClass("block-editor-page")) {
        return true;
      }

      // Способ 2: Проверяем по наличию Gutenberg элементов
      if (
        $(".edit-post-layout").length > 0 ||
        $(".block-editor").length > 0 ||
        $("#editor").hasClass("block-editor__container")
      ) {
        return true;
      }

      // Способ 3: Проверяем по window.wp
      if (
        typeof window.wp !== "undefined" &&
        window.wp.data &&
        window.wp.data.select("core/editor")
      ) {
        return true;
      }

      // Способ 4: По локализованным данным
      if (window.rdsAIExcerptWidget && window.rdsAIExcerptWidget.isGutenberg) {
        return true;
      }

      return false;
    };

    /**
     * Initialize widget for Classic Editor
     */
    this.initClassicWidget = function () {
      console.log("Initializing RDS AI Excerpt Widget for Classic Editor...");

      // Проверяем, не в Gutenberg ли мы
      if (this.isGutenbergEditor()) {
        console.log("Detected Gutenberg editor, disabling Classic widget");
        return;
      }

      this.bindEvents();
    };

    /**
     * Bind event handlers
     */
    this.bindEvents = function () {
      // Generate button
      $("#rds-ai-excerpt-generate").on("click", function (e) {
        e.preventDefault();
        console.log("Generate button clicked in Classic Editor");
        self.generateExcerpt(); // Используем self вместо this
      });

      // Apply button
      $("#rds-ai-excerpt-apply").on("click", function (e) {
        e.preventDefault();
        console.log("Apply button clicked in Classic Editor");
        self.applyExcerpt(); // Используем self вместо this
      });

      // Copy button
      $("#rds-ai-excerpt-copy").on("click", function (e) {
        e.preventDefault();
        console.log("Copy button clicked in Classic Editor");
        self.copyExcerpt(); // Используем self вместо this
      });

      // Parameter changes
      $(
        "#rds-ai-excerpt-style, #rds-ai-excerpt-tone, #rds-ai-excerpt-language, #rds-ai-excerpt-max-length, #rds-ai-excerpt-focus-keywords"
      ).on("change", function () {
        self.hideResult();
      });
    };

    /**
     * Generate excerpt
     */
    this.generateExcerpt = function () {
      // Get widget data from localized object or fallback
      const widgetData = window.rdsAIExcerptWidget || {
        ajaxUrl: ajaxurl,
        nonce: "",
        postId: $("#post_ID").val() || 0,
      };

      const postId = widgetData.postId || $("#post_ID").val();

      console.log("Generating excerpt for post (Classic):", postId);

      // Get parameters
      const params = {
        style: $("#rds-ai-excerpt-style").val(),
        tone: $("#rds-ai-excerpt-tone").val(),
        language: $("#rds-ai-excerpt-language").val(),
        max_length: $("#rds-ai-excerpt-max-length").val(),
        focus_keywords: $("#rds-ai-excerpt-focus-keywords").val(),
      };

      console.log("Parameters (Classic):", params);

      // Validate
      if (!params.max_length || params.max_length < 50) {
        this.showError("Minimum length is 50 words.");
        return;
      }

      // Show loading
      this.showLoading();
      this.hideError();
      this.hideResult();

      // Send request
      $.ajax({
        url: widgetData.ajaxUrl || ajaxurl,
        type: "POST",
        dataType: "json",
        data: {
          action: "rds_ai_generate_excerpt",
          nonce: widgetData.nonce,
          post_id: postId,
          ...params,
        },
        success: function (response) {
          console.log("API Response (Classic):", response);
          self.hideLoading();

          if (response.success) {
            self.showResult(response.excerpt);
          } else {
            self.showError(response.error || "Unknown error occurred.");
          }
        },
        error: function (xhr, status, error) {
          console.error("API Error (Classic):", xhr, status, error);
          self.hideLoading();
          self.showError(
            "Failed to generate excerpt. Please try again. Error: " + error
          );
        },
      });
    };

    /**
     * Apply excerpt to post
     */
    this.applyExcerpt = function () {
      const excerpt = $("#rds-ai-excerpt-output").val().trim();

      if (!excerpt) {
        return;
      }

      console.log("Applying excerpt (Classic):", excerpt);

      // Find excerpt field
      let excerptField = $("#excerpt");

      // For some themes, excerpt field might have different ID
      if (!excerptField.length) {
        excerptField = $('textarea[name="excerpt"]');
      }

      if (excerptField.length) {
        excerptField.val(excerpt);

        // Show success message
        this.showTemporaryMessage("Excerpt applied successfully!", "success");
      } else {
        this.showError("Could not find excerpt field.");
      }
    };

    /**
     * Copy excerpt to clipboard
     */
    this.copyExcerpt = function () {
      const excerpt = $("#rds-ai-excerpt-output").val().trim();

      if (!excerpt) {
        return;
      }

      try {
        // Create temporary textarea
        const tempTextarea = $("<textarea>");
        $("body").append(tempTextarea);
        tempTextarea.val(excerpt).select();

        // Execute copy command
        document.execCommand("copy");
        tempTextarea.remove();

        // Show success message
        this.showTemporaryMessage("Copied to clipboard!", "success");
      } catch (err) {
        this.showError("Failed to copy to clipboard.");
      }
    };

    /**
     * Show loading indicator
     */
    this.showLoading = function () {
      console.log("Showing loading...");
      $(".rds-ai-excerpt-loading")
        .show()
        .html(
          '<p><span class="spinner is-active" style="float:none;margin:0 8px 0 0"></span>Generating excerpt...</p>'
        );
      $("#rds-ai-excerpt-generate").prop("disabled", true);
    };

    /**
     * Hide loading indicator
     */
    this.hideLoading = function () {
      console.log("Hiding loading...");
      $(".rds-ai-excerpt-loading").hide();
      $("#rds-ai-excerpt-generate").prop("disabled", false);
    };

    /**
     * Show error message
     */
    this.showError = function (message) {
      console.log("Showing error:", message);
      $(".rds-ai-excerpt-error .error-message").text(message);
      $(".rds-ai-excerpt-error").show();
    };

    /**
     * Hide error message
     */
    this.hideError = function () {
      $(".rds-ai-excerpt-error").hide();
    };

    /**
     * Show result
     */
    this.showResult = function (excerpt) {
      console.log("Showing result:", excerpt);
      $("#rds-ai-excerpt-output").val(excerpt);
      $(".rds-ai-excerpt-result").show();

      // Scroll to result
      $("html, body").animate(
        {
          scrollTop: $(".rds-ai-excerpt-result").offset().top - 100,
        },
        500
      );
    };

    /**
     * Hide result
     */
    this.hideResult = function () {
      $(".rds-ai-excerpt-result").hide();
    };

    /**
     * Show temporary message
     */
    this.showTemporaryMessage = function (message, type) {
      const messageClass =
        type === "success" ? "notice-success" : "notice-error";
      const notice = $(
        '<div class="notice ' +
          messageClass +
          ' is-dismissible"><p>' +
          message +
          "</p></div>"
      );

      // Insert after widget
      $("#rds-ai-excerpt-generator").after(notice);

      // Auto-remove after 5 seconds
      setTimeout(function () {
        notice.fadeOut(500, function () {
          $(this).remove();
        });
      }, 5000);

      // Allow manual dismissal
      notice.on("click", ".notice-dismiss", function () {
        $(this).parent().remove();
      });
    };

    /**
     * Set default values from settings
     */
    this.setDefaults = function (defaults) {
      if (!defaults) return;

      $("#rds-ai-excerpt-style").val(defaults.style || "descriptive");
      $("#rds-ai-excerpt-tone").val(defaults.tone || "neutral");
      $("#rds-ai-excerpt-language").val(defaults.language || "en");
      $("#rds-ai-excerpt-max-length").val(defaults.maxLength || 150);
      $("#rds-ai-excerpt-focus-keywords").val(defaults.focusKeywords || "");
    };
  }

  // Initialize when document is ready
  $(document).ready(function () {
    console.log("Document ready, checking editor type...");

    // Проверяем метабокс существует ли
    if ($("#rds-ai-excerpt-generator").length === 0) {
      console.log("RDS AI Excerpt meta box not found, skipping initialization");
      return;
    }

    // Create widget instance
    var widgetInstance = new RDSAIExcerptWidget();

    // Set default values
    if (window.rdsAIExcerptWidget && window.rdsAIExcerptWidget.defaults) {
      widgetInstance.setDefaults(window.rdsAIExcerptWidget.defaults);
    }

    // Initialize widget
    widgetInstance.initClassicWidget();
  });
})(jQuery);
