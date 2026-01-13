=== RDS AI Excerpt Generator ===
Contributors: yourusername
Donate link: https://yourwebsite.com
Tags: excerpt, ai, openai, automatic, content, generation
Requires at least: 6.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generate post excerpts using AI based on post content.

== Description ==

RDS AI Excerpt Generator is a WordPress plugin that uses artificial intelligence to automatically generate engaging and relevant excerpts for your posts.

= Key Features =

* **AI-Powered Excerpt Generation**: Uses OpenAI-compatible APIs to generate excerpts
* **Multiple Styles**: Choose from descriptive, advertising, business, or creative styles
* **Customizable Tone**: Set the tone (formal, friendly, neutral, professional)
* **Multilingual Support**: Generate excerpts in multiple languages
* **Focus Keywords**: Specify keywords to focus on in the excerpt
* **Gutenberg & Classic Editor Support**: Works with both WordPress editors
* **Secure API Handling**: All API calls are made server-side
* **Role-Based Access Control**: Control which user roles can use the plugin
* **Debug Logging**: Optional logging for troubleshooting

= How It Works =

1. Install and activate the plugin
2. Configure your AI API settings (OpenAI or compatible)
3. Edit any post and find the "AI Excerpt Generator" panel in the sidebar
4. Adjust generation parameters (style, tone, language, etc.)
5. Click "Generate Excerpt" to create an AI-powered excerpt
6. Review and apply the generated excerpt to your post

= Requirements =

* WordPress 6.0 or higher
* PHP 7.4 or higher
* An OpenAI API key or compatible AI API

= Privacy & Security =

* All API keys are stored securely in your WordPress database
* API calls are made server-side (keys are never exposed to browsers)
* You control which data is sent to the AI service

== Installation ==

1. Upload the `rds-ai-excerpt-generator` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Settings > AI Excerpt to configure your API settings
4. Start generating excerpts in the post editor!

== Frequently Asked Questions ==

= What AI services are supported? =

The plugin supports any OpenAI-compatible API. This includes:
* OpenAI's official API
* Azure OpenAI Service
* Local AI servers with OpenAI-compatible endpoints
* Other compatible AI providers

= Is my API key secure? =

Yes! All API calls are made from your server. Your API key is never exposed to the browser or sent to client-side JavaScript.

= Can I use this with other post types? =

Yes! By default, it works with posts, but you can enable it for any public post type in the settings.

= How much does it cost? =

The plugin itself is free. You only pay for API usage with your AI provider (OpenAI, etc.).

== Screenshots ==

1. Settings page for configuring API and defaults
2. AI Excerpt Generator panel in Gutenberg editor
3. Generated excerpt preview with apply/copy options
4. Classic editor meta box with generation parameters

== Changelog ==

= 1.0.0 =
* Initial release
* Support for Gutenberg and Classic editors
* Configurable AI API settings
* Multiple generation styles and tones
* Multilingual support
* Role-based access control
* Debug logging

== Upgrade Notice ==

= 1.0.0 =
Initial release. No upgrade required.