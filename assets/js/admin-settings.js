/**
 * RDS AI Excerpt Generator - Admin Settings Page
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    // Connection Test Button
    $("#rds-ai-test-connection").on("click", function (e) {
      e.preventDefault();
      testAIConnection();
    });

    // Enable/disable test button based on model selection
    $("#rds_ai_excerpt_selected_model_id").on("change", function () {
      var selectedModel = $(this).val();
      var testButton = $("#rds-ai-test-connection");

      if (selectedModel) {
        testButton.prop("disabled", false);

        // Update connection status
        updateConnectionStatus();
      } else {
        testButton.prop("disabled", true);
        $("#rds-ai-connection-status").html(
          '<p class="notice notice-warning" style="margin: 0; padding: 10px;">' +
            rdsAIExcerptSettings.strings.noModel +
            "</p>"
        );
      }
    });

    // Update connection status on page load
    updateConnectionStatus();

    // Character counter for system prompt
    const systemPrompt = $("#rds_ai_excerpt_system_prompt");
    if (systemPrompt.length) {
      const counter = $(
        '<div class="char-counter" style="margin-top: 5px; font-size: 12px; color: #666;"></div>'
      );
      systemPrompt.after(counter);

      function updateCounter() {
        const length = systemPrompt.val().length;
        counter.text(length + " characters");

        if (length > 2000) {
          counter.css("color", "#d63638");
        } else if (length > 1000) {
          counter.css("color", "#dba617");
        } else {
          counter.css("color", "#50575e");
        }
      }

      systemPrompt.on("input", updateCounter);
      updateCounter();
    }

    /**
     * Update connection status display
     */
    function updateConnectionStatus() {
      var selectedModel = $("#rds_ai_excerpt_selected_model_id").val();
      var modelName = $(
        "#rds_ai_excerpt_selected_model_id option:selected"
      ).text();

      if (selectedModel && modelName !== "-- Select Model --") {
        $("#rds-ai-connection-status").html(
          "<p><strong>" +
            rdsAIExcerptSettings.strings.testing +
            "</strong><br>" +
            modelName +
            "</p>"
        );
      }
    }

    /**
     * Test AI Connection
     */
    function testAIConnection() {
      const button = $("#rds-ai-test-connection");
      const originalText = button.text();
      const resultDiv = $("#rds-ai-test-result");
      const selectedModel = $("#rds_ai_excerpt_selected_model_id").val();

      // Validate
      if (!selectedModel) {
        showResult(rdsAIExcerptSettings.strings.noModel, "error");
        return;
      }

      // Show loading
      button.prop("disabled", true).text(rdsAIExcerptSettings.strings.testing);
      resultDiv.hide().removeClass("success error");

      // Send test request
      $.ajax({
        url: rdsAIExcerptSettings.ajaxurl,
        type: "POST",
        dataType: "json",
        data: {
          action: "rds_ai_test_connection",
          nonce: rdsAIExcerptSettings.nonce,
          model_id: selectedModel,
        },
        success: function (response) {
          console.log("Connection Test Response:", response);
          if (response.success) {
            showResult(
              response.message || rdsAIExcerptSettings.strings.testSuccess,
              "success"
            );
          } else {
            showResult(
              response.error || rdsAIExcerptSettings.strings.testFailed,
              "error"
            );
          }
        },
        error: function (xhr, status, error) {
          console.error("Connection Test Error:", status, error);
          showResult(
            rdsAIExcerptSettings.strings.testError + ": " + (error || status),
            "error"
          );
        },
        complete: function () {
          button.prop("disabled", false).text("Test AI Connection");
        },
      });
    }

    /**
     * Show test result
     */
    function showResult(message, type) {
      const resultDiv = $("#rds-ai-test-result");
      const icon =
        type === "success"
          ? '<span class="dashicons dashicons-yes" style="color: #46b450; margin-right: 5px;"></span>'
          : '<span class="dashicons dashicons-no" style="color: #d63638; margin-right: 5px;"></span>';

      resultDiv
        .removeClass("success error")
        .addClass(type)
        .html(
          "<p>" +
            icon +
            "<strong>" +
            (type === "success" ? "Success:" : "Error:") +
            "</strong> " +
            message +
            "</p>"
        )
        .show()
        .css({
          background: type === "success" ? "#d4edda" : "#f8d7da",
          border: "1px solid " + (type === "success" ? "#c3e6cb" : "#f5c6cb"),
          color: type === "success" ? "#155724" : "#721c24",
          "border-radius": "4px",
        });

      // Auto-hide success messages after 5 seconds
      if (type === "success") {
        setTimeout(function () {
          resultDiv.fadeOut(500);
        }, 5000);
      }
    }
  });
})(jQuery);
