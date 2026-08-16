# AV-CRM AI System — Feature Documentation Index

This directory contains comprehensive technical documentation for all features within the **AV-CRM AI** codebase. Each document provides deep architectural analysis, end-to-end data flow traces, code snippets, database schemas, and edge case evaluations for maintainers and developers.

---

## Features by Domain

### 1. Core Authentication & Access Control
| Feature | Summary | Documentation |
|---|---|---|
| **User Authentication & Profile Management** | Session-based authentication, registration, user/admin role differentiation, and self-service profile updating. | [user-authentication-and-profile.md](file:///h:/laragon/www/avcrm-ai/docs/features/user-authentication-and-profile.md) |

### 2. Artificial Intelligence & Vector Knowledge Engine
| Feature | Summary | Documentation |
|---|---|---|
| **AI Provider Abstraction Layer** | Infrastructure-agnostic abstraction supporting interchangeable LLM and embedding drivers (Ollama, Gemini, Groq) via OpenAI-compatible endpoints. | [ai-provider-abstraction.md](file:///h:/laragon/www/avcrm-ai/docs/features/ai-provider-abstraction.md) |
| **Knowledge Base Ingestion & Vector Pipeline** | Multi-project markdown parser, sliding-window chunker, binary BLOB vector encoder (`VectorCodec`), and batch ingestion CLI. | [knowledge-ingestion-and-vector-pipeline.md](file:///h:/laragon/www/avcrm-ai/docs/features/knowledge-ingestion-and-vector-pipeline.md) |
| **Multilingual AI FAQ Chat & RAG Engine** | Production RAG assistant supporting English, Bengali Unicode, and Banglish transliteration with SSE streaming, query reformulation, and prompt injection defense. | [multilingual-rag-chat.md](file:///h:/laragon/www/avcrm-ai/docs/features/multilingual-rag-chat.md) |

### 3. Support Operations & Quality Feedback
| Feature | Summary | Documentation |
|---|---|---|
| **Issue & Ticket Management System** | Complete CRUD issue tracking with attachment handling, paired with conversational AI intent detection for in-chat issue creation and status lookup. | [issue-and-ticket-management.md](file:///h:/laragon/www/avcrm-ai/docs/features/issue-and-ticket-management.md) |
| **Chat Feedback Collection & Quality Insights** | User response satisfaction tracking (thumbs up/down + qualitative commentary) with an administrative web dashboard and CLI analytics. | [chat-feedback-and-insights.md](file:///h:/laragon/www/avcrm-ai/docs/features/chat-feedback-and-insights.md) |

---

## Architectural Overview

```
                      ┌────────────────────────────────────────┐
                      │              Web Client                │
                      └──────────────────┬─────────────────────┘
                                         │
                 ┌───────────────────────┼───────────────────────┐
                 │ HTTP (Blade / SSE)    │ AJAX / API            │ Session Auth
                 ▼                       ▼                       ▼
    ┌─────────────────────────┐  ┌───────────────┐   ┌───────────────────────┐
    │    FaqChatController    │  │IssueController│   │AuthController/Profile │
    └────────────┬────────────┘  └───────┬───────┘   └───────────┬───────────┘
                 │                       │                       │
         ┌───────┴────────┐              │                       ▼
         ▼                ▼              ▼                 `users` table
 ┌───────────────┐ ┌──────────────┐┌───────────────┐
 │ RagAnswerer   │ │IssueAiService││ Issues/Files  │
 └───────┬───────┘ └──────┬───────┘└───────┬───────┘
         │                │                │
         ▼                ▼                ▼
 ┌───────────────┐ ┌──────────────┐┌────────────────┐
 │  FaqRetriever │ │ Intent/State ││ `issues`,      │
 └───────┬───────┘ └──────────────┘│ `attachments`, │
         │                         │ `feedbacks`    │
         ▼                         └────────────────┘
 ┌────────────────────────────────┐
 │ AI Provider Layer              │
 │ - EmbeddingProvider (Vectors)  │
 │ - ChatProvider (LLM / SSE)     │
 └────────────────────────────────┘
```

---

## Quick Navigation
- [User Authentication & Profile Management](file:///h:/laragon/www/avcrm-ai/docs/features/user-authentication-and-profile.md)
- [AI Provider Abstraction Layer](file:///h:/laragon/www/avcrm-ai/docs/features/ai-provider-abstraction.md)
- [Knowledge Base Ingestion & Vector Pipeline](file:///h:/laragon/www/avcrm-ai/docs/features/knowledge-ingestion-and-vector-pipeline.md)
- [Multilingual AI FAQ Chat & RAG Engine](file:///h:/laragon/www/avcrm-ai/docs/features/multilingual-rag-chat.md)
- [Issue & Ticket Management System](file:///h:/laragon/www/avcrm-ai/docs/features/issue-and-ticket-management.md)
- [Chat Feedback Collection & Quality Insights](file:///h:/laragon/www/avcrm-ai/docs/features/chat-feedback-and-insights.md)
