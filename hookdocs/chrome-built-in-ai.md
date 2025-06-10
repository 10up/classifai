Chrome is experimenting with adding [built-in AI](https://developer.chrome.com/docs/ai/built-in) directly to the browser, allowing websites and web applications to perform AI-powered tasks without needing to deploy or manage their own AI models. Please follow the steps below to use Chrome’s built-in AI:

1. **Install Chrome Canary:** Ensure you have version v128.0.6545.0 or newer.
2. **Enable Optimization Guide:** Open `chrome://flags/#optimization-guide-on-device-model`, set it to "Enabled BypassPerfRequirement".
3. **Enable Prompt API:** Open `chrome://flags/#prompt-api-for-gemini-nano`, set it to "Enabled".
4. **Relaunch Chrome**.
5. **Open DevTools** and send `await ai.languageModel.capabilities().available;` in the console. If this returns “readily,” then you are all set.
6. **If it fails**, force Chrome to recognize that you want to use this API. To do so, open DevTools and send `await ai.languageModel.create();` in the console. This will likely fail, but it’s intended.
7. **Relaunch Chrome**.
8. **Open a new tab in Chrome**, go to `chrome://components`.
9. **Confirm** that Gemini Nano is either available or is being downloaded. You'll want to see the Optimization Guide On Device Model present with a version greater or equal to 2024.5.21.1031. If there is no version listed, click on "Check for update" to force the download.
10. **Once the model has downloaded and has reached a version greater than shown above**, open DevTools and send `await ai.languageModel.capabilities().available;` in the console. If this returns “readily,” then you are all set.
