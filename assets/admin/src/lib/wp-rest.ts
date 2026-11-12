// assets/admin/src/lib/wp-rest.ts

import type { OssrRestConfig } from "@/types/wp";

type RestErrorResponse = {
    code?: string;
    message?: string;
    data?: {
        status?: number;
        params?: Record<string, string>;
        details?: Record<string, { message?: string }>;
    };
};

function getRestConfig(): OssrRestConfig {
    const cfg = window.ossrRestConfig;

    if (!cfg?.restUrl || !cfg?.nonce) {
        throw new Error("OSSR REST config is missing.");
    }

    return { restUrl: cfg.restUrl, nonce: cfg.nonce };
}

function buildUrl(restUrl: string, path: string): string {
    return `${restUrl.replace(/\/+$/, "")}/${path.replace(/^\/+/, "")}`;
}

async function parseResponseBody(res: Response): Promise<unknown> {
    const text = await res.text();

    if (text === "") {
        return null;
    }

    try {
        return JSON.parse(text);
    } catch {
        return text;
    }
}

function getErrorMessage(data: unknown, res: Response): string {
    if (typeof data === "object" && data !== null && "message" in data) {
        const errorData = data as RestErrorResponse;

        const paramMessages = errorData.data?.params
            ? Object.values(errorData.data.params).filter(
                  (message): message is string =>
                      typeof message === "string" && message !== "",
              )
            : [];

        const detailMessages = errorData.data?.details
            ? Object.values(errorData.data.details)
                  .map((detail) => detail.message)
                  .filter(
                      (message): message is string =>
                          typeof message === "string" && message !== "",
                  )
            : [];

        const messages = [...paramMessages, ...detailMessages];

        if (messages.length > 0) {
            return [...new Set(messages)].join("\n");
        }

        if (typeof errorData.message === "string" && errorData.message !== "") {
            return errorData.message;
        }
    }

    if (typeof data === "string" && data !== "") {
        return data;
    }

    return `REST ${res.status} ${res.statusText}`;
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
    const { restUrl, nonce } = getRestConfig();

    const headers = new Headers(init.headers);
    headers.set("X-WP-Nonce", nonce);

    const res = await fetch(buildUrl(restUrl, path), {
        ...init,
        headers,
    });

    // It can handle cases like 204 No content.
    if (res.status === 204) {
        if (!res.ok) {
            throw new Error(`REST ${res.status} ${res.statusText}`);
        }

        return undefined as T;
    }

    const data = await parseResponseBody(res);

    // Do not ignore 4xx/5xx responses.
    if (!res.ok) {
        throw new Error(getErrorMessage(data, res));
    }

    return data as T;
}

export const wpRest = {
    get<T>(path: string): Promise<T> {
        return request<T>(path, { method: "GET" });
    },
    post<T>(path: string, body?: unknown): Promise<T> {
        return request<T>(path, {
            method: "POST",
            ...(body !== undefined
                ? {
                      headers: { "Content-Type": "application/json" },
                      body: JSON.stringify(body),
                  }
                : {}),
        });
    },
    patch<T>(path: string, body: unknown): Promise<T> {
        return request<T>(path, {
            method: "PATCH",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(body),
        });
    },
    put<T>(path: string, body: unknown): Promise<T> {
        return request<T>(path, {
            method: "PUT",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(body),
        });
    },
    delete<T>(path: string): Promise<T> {
        return request<T>(path, { method: "DELETE" });
    },
};
