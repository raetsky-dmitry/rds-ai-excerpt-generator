/**
 * RDS AI Excerpt Generator - Classic Editor Widget
 */

(function ($) {
  "use strict";

  /**
   * Main Widget Class
   */
  function RDSAIExcerptWidget() {
    this.initClassicWidget = function () {
      console.log("Initializing RDS AI Excerpt Widget...");
      this.bindEvents();
      this.hideExcerptFieldIfEmpty();
    }.bind(this);

    /**
     * Bind event handlers
     */
    this.bindEvents = function () {
      const self = this;

      // Generate button
      $("#rds-ai-excerpt-generate").on("click", function (e) {
        e.preventDefault();
        console.log("Generate button clicked");
        self.generateExcerpt();
      });

      // Apply button
      $("#rds-ai-excerpt-apply").on("click", function (e) {
        e.preventDefault();
        console.log("Apply button clicked");
        self.applyExcerpt();
      });

      // Copy button
      $("#rds-ai-excerpt-copy").on("click", function (e) {
        e.preventDefault();
        console.log("Copy button clicked");
        self.copyExcerpt();
      });

      // Parameter changes
      $(
        "#rds-ai-excerpt-style, #rds-ai-excerpt-tone, #rds-ai-excerpt-language, #rds-ai-excerpt-max-length, #rds-ai-excerpt-focus-keywords"
      ).on("change", function () {
        self.hideResult();
      });
    }.bind(this);

    /**
     * Generate excerpt
     */
    this.generateExcerpt = function () {
      const self = this;

      // Get widget data from localized object or fallback
      const widgetData = window.rdsAIExcerptWidget || {
        ajaxUrl: ajaxurl,
        nonce: "",
        postId: $("#post_ID").val() || 0,
      };

      const postId = widgetData.postId || $("#post_ID").val();

      console.log("Generating excerpt for post:", postId);

      // Get parameters
      const params = {
        style: $("#rds-ai-excerpt-style").val(),
        tone: $("#rds-ai-excerpt-tone").val(),
        language: $("#rds-ai-excerpt-language").val(),
        max_length: $("#rds-ai-excerpt-max-length").val(),
        focus_keywords: $("#rds-ai-excerpt-focus-keywords").val(),
      };

      console.log("Parameters:", params);

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
          console.log("API Response:", response);
          self.hideLoading();

          if (response.success) {
            self.showResult(response.excerpt);
          } else {
            self.showError(response.error || "Unknown error occurred.");
          }
        },
        error: function (xhr, status, error) {
          console.error("API Error:", xhr, status, error);
          self.hideLoading();
          self.showError(
            "Failed to generate excerpt. Please try again. Error: " + error
          );
        },
      });
    }.bind(this);

    /**
     * Apply excerpt to post
     */
    this.applyExcerpt = function () {
      const excerpt = $("#rds-ai-excerpt-output").val().trim();

      if (!excerpt) {
        return;
      }

      console.log("Applying excerpt:", excerpt);

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
    }.bind(this);

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
    }.bind(this);

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
    }.bind(this);

    /**
     * Hide loading indicator
     */
    this.hideLoading = function () {
      console.log("Hiding loading...");
      $(".rds-ai-excerpt-loading").hide();
      $("#rds-ai-excerpt-generate").prop("disabled", false);
    }.bind(this);

    /**
     * Show error message
     */
    this.showError = function (message) {
      console.log("Showing error:", message);
      $(".rds-ai-excerpt-error .error-message").text(message);
      $(".rds-ai-excerpt-error").show();
    }.bind(this);

    /**
     * Hide error message
     */
    this.hideError = function () {
      $(".rds-ai-excerpt-error").hide();
    }.bind(this);

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
    }.bind(this);

    /**
     * Hide result
     */
    this.hideResult = function () {
      $(".rds-ai-excerpt-result").hide();
    }.bind(this);

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
    }.bind(this);

    /**
     * Hide excerpt field if empty (for better UX)
     */
    this.hideExcerptFieldIfEmpty = function () {
      // This is just a helper function, not essential
      const excerptField = $("#excerpt");
      if (excerptField.length && !excerptField.val().trim()) {
        // Optional: you could hide or collapse the excerpt metabox here
      }
    }.bind(this);

    /**
     * Set default values from settings
     */
    this.setDefaults = function (defaults) {
      if (!defaults) return;

      $("#rds-ai-excerpt-style").val(defaults.style || "descriptive");
      $("#rds-ai-excerpt-tone").val(defaults.tone || "neutral");
      $("#rds-ai-excerpt-language").val(defaults.language || "en");
      $("#rds-ai-excerpt-max-length").val(defaults.maxLength || 150);
    }.bind(this);
  }

  // Initialize when document is ready
  $(document).ready(function () {
    console.log("Document ready, initializing widget...");

    // Create widget instance
    window.rdsAIExcerptWidgetInstance = new RDSAIExcerptWidget();

    // Get localized data if available
    const widgetData = window.rdsAIExcerptWidget || {};

    // Set default values
    if (widgetData.defaults) {
      window.rdsAIExcerptWidgetInstance.setDefaults(widgetData.defaults);
    }

    // Initialize widget
    window.rdsAIExcerptWidgetInstance.initClassicWidget();
  });
})(jQuery);
