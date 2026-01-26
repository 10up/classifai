---
id: provider-configuration
title: "Provider Configuration"
sidebar_label: "Provider Configuration"
---

# Provider Configuration

Welcome to the ClassifAI Provider Configuration guide. This section provides detailed configuration instructions for all available Providers in ClassifAI.

## Global Provider Configuration

The recommended way to configure Providers in ClassifAI is to use the global Providers settings page. This allows you to configure each desired Provider once and those can then be assigned to all required Features.

Go to `Tools > ClassifAI > Providers` to configure the Providers. On this page, find the Providers you want to configure, click to toggle them open and then add in any needed credentials, like API keys. You can then save the configuration settings for that Provider and if valid, they can be used by any Feature that requires them.

## Feature-Specific Provider Configuration

In some cases you may want to use different Provider credentials depending on the Feature you are using. For example, you may want to use a different API key for each individual Feature you have enabled, allowing you more fine-grained usage tracking.

You can do this when you are [configuring a specific Feature](/docs/03.feature-configuration/index.md). After choosing which Provider you want to use for that Feature, you will see an `Override Provider credentials` toggle. If you enable this, you will be able to configure the Provider credentials for that specific Feature and those credentials will be used instead of the global credentials.
