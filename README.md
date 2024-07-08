# Simple AI Assistant

## Description

Simple AI Assistant is a powerful and customizable WordPress plugin that integrates an AI-powered chatbot into your website. Using OpenAI's GPT models, this plugin provides an interactive chat experience for your visitors while prioritizing security, privacy, and compliance with data protection regulations.
You can visually customize everything you need to import the bot into your website and blend it with your required style, you can choose who has access to it, and you can also decide if you want your bot to have knowledge about the chat history or about the current page
## Features

- **AI-Powered Chat**: Utilizes OpenAI's GPT models for intelligent conversations.
- **Customizable Appearance**: Easily adjust the chat's text, color, and position.
- **Flexible Context Options**: Choose how much context to provide to the AI, including page content and chat history.
- **User Role Management**: Control which user roles can access the chat feature.
- **Rate Limiting**: Prevent abuse with configurable rate limits.
- **Token Usage Tracking**: Monitor and manage your OpenAI API usage.
- **GDPR Compliance**: 
  - Customizable data retention periods
  - User data deletion on request
  - User data deletion handled automatically by the plugin, NO actions needed on your side
  - Privacy policy content generation
- **Secure API Key Handling**: Encrypts the OpenAI API key for enhanced security.
- **Detailed Logging**: Comprehensive logging system for monitoring and troubleshooting.
- **Export Functionality**: Export chat logs for analysis or record-keeping.
- **Responsive Design**: Works seamlessly on both desktop and mobile devices.

## Installation

1. Upload the `simple-ai-assistant` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to 'Settings' > 'Simple AI Assistant' to configure the plugin.

## Configuration

### General Settings

- **OpenAI API Key**: Enter your OpenAI API key (securely encrypted).
- **OpenAI Model**: Choose between GPT-3.5-turbo and GPT-4.
- **System Message**: Customize the AI's behavior with a system message.
- **Context Options**: Choose if your bot should have knowledge about your current page content, if it only has memory of previous messages, or both.

### Appearance

The Graphic Settings page should give you all the customization options you will need to give your bot the best style, you can also choose where it should display the chat bubble

### Behavior

- **Rate Limit**: Set the maximum number of requests per hour per user.
- **Max Tokens**: Limit the length of AI responses.
- **Temperature**: Adjust the randomness of AI responses.
- **Welcome Message**: Set a custom greeting for new chat sessions.

### Context and Privacy

- **Context Options**: Choose how much context to provide to the AI.
- **User Consent**: Enable or disable user consent requirement.
- **Data Retention**: Set the number of days to retain chat logs.

### Access Control

- **Enable on Pages**: Choose where the chat assistant appears.
- **Allowed User Roles**: Select which user roles can use the chat.

## Usage & tips

Once configured, the chat bubble will appear on your website according to your settings. Visitors can click on it to start a conversation with the AI assistant.
If the user consent option is enabled, the user will have to accept that the logs will be saved. Remember to update your site's privacy policy.

Use the system message to give your bot limits to the answer length. Something simple like "your answer shouldnt go over 200 words" almost always works. 
Pay attention with the context options. Giving full context to your bot makes it a lot more powerful, it can remember everything and give the most useful answers, but your token usage will grow A LOT.
Always look at your limits on the OpenAI platform and choose wisely if you really need all the context. 
For example, if you are looking for an helper related to specific pages, you can choose to only pass the page context without the chat history.


## Security Considerations

- The OpenAI API key is encrypted before being stored in the database.
- Rate limiting helps prevent abuse of the system.
- User input is sanitized to prevent XSS attacks.

## Privacy and GDPR Compliance

- Users can request deletion of their chat data.
- Configurable data retention periods.
- Automatically generates privacy policy content.
- Option to require user consent before using the chat.

## Logging and Monitoring

- Detailed logs of chat interactions and system events.
- Token usage tracking for monitoring API consumption.
- Export functionality for chat logs.

## Frequently Asked Questions

**Q: How do I get an OpenAI API key?**
A: You need to sign up for an account at [OpenAI's website](https://openai.com) and subscribe to their API service.

**Q: Can I customize the AI's responses?**
A: Yes, every needed option for customization is included in the plugin settings page.

**Q: Is this plugin GDPR compliant?**
A: The plugin includes features to help with GDPR compliance, such as data retention controls and user data deletion. However, ensure your overall WordPress setup and usage of the plugin aligns with GDPR requirements.

## Support

For support, feel free to open an issue or contact me, I'll do my best to help.

## Contributing

Contributions are always welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL2 License.

## Changelog


### 0.9
- Initial release

