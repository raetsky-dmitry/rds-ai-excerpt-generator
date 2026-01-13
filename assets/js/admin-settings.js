/**
 * RDS AI Excerpt Generator - Admin Settings Page
 */

(function ($) {
  "use strict";

  $(document).ready(function () {
    // API Test Button
    $("#rds-ai-test-api").on("click", function (e) {
      e.preventDefault();
      testAPIConnection();
    });

    // Show/Hide API Key
    $("#rds-ai-toggle-api-key").on("click", function (e) {
      e.preventDefault();
      const apiKeyInput = $("#rds_ai_excerpt_settings_api_key");
      const type =
        apiKeyInput.attr("type") === "password" ? "text" : "password";
      apiKeyInput.attr("type", type);
      $(this).text(type === "password" ? "Show" : "Hide");
    });

    // Character counter for system prompt
    const systemPrompt = $("#rds_ai_excerpt_settings_system_prompt");
    if (systemPrompt.length) {
      const counter = $(
        '<div class="char-counter" style="margin-top: 5px; font-size: 12px; color: #666;"></div>'
      );
      systemPrompt.after(counter);

      function updateCounter() {
        const length = systemPrompt.val().length;
        counter.text(length + " characters");

        if (length > 1000) {
          counter.css("color", "#d63638");
        } else if (length > 500) {
          counter.css("color", "#dba617");
        } else {
          counter.css("color", "#50575e");
        }
      }

      systemPrompt.on("input", updateCounter);
      updateCounter();
    }

    /**
     * Test API Connection
     */
    function testAPIConnection() {
      const button = $("#rds-ai-test-api");
      const originalText = button.text();
      const resultDiv = $(".api-test-result");

      // Show loading
      button.prop("disabled", true).text("Testing...");
      resultDiv.hide().removeClass("success error");

      // Get values directly from form fields
      const apiKey = $("#rds_ai_excerpt_settings_api_key").val();
      const baseUrl = $("#rds_ai_excerpt_settings_api_base_url").val();

      console.log("Testing API with:", {
        apiKey: apiKey ? "***" : "empty",
        baseUrl,
      });

      if (!apiKey) {
        showResult("Please enter an API key first.", "error");
        button.prop("disabled", false).text(originalText);
        return;
      }

      if (!baseUrl) {
        showResult("Please enter a base URL first.", "error");
        button.prop("disabled", false).text(originalText);
        return;
      }

      // Send test request
      $.ajax({
        url: window.rdsAISettings.ajaxurl,
        type: "POST",
        dataType: "json",
        data: {
          action: "rds_ai_test_api_connection",
          nonce: window.rdsAISettings.nonce,
          api_key: apiKey,
          base_url: baseUrl,
        },
        success: function (response) {
          console.log("API Test Response:", response);
          if (response.success) {
            showResult(
              response.message || "API connection successful!",
              "success"
            );
          } else {
            showResult(response.error || "API connection failed.", "error");
          }
        },
        error: function (xhr, status, error) {
          console.error("API Test Error:", status, error);
          showResult("Error: " + (error || status), "error");
        },
        complete: function () {
          button.prop("disabled", false).text(originalText);
        },
      });
    }

    /**
     * Show test result
     */
    function showResult(message, type) {
      const resultDiv = $(".api-test-result");
      resultDiv
        .removeClass("success error")
        .addClass(type)
        .html(
          "<p><strong>" +
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
          padding: "10px",
        });

      // Scroll to result
      $("html, body").animate(
        {
          scrollTop: resultDiv.offset().top - 100,
        },
        500
      );
    }
  });
})(jQuery);
