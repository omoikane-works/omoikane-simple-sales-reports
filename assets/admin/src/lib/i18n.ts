// assets/admin/src/lib/i18n.ts
import type { OssrI18n } from "@/types/wp";

function isRecordStringString(value: unknown): value is Record<string, string> {
    if (value === null || typeof value !== "object" || Array.isArray(value)) {
        return false;
    }

    return Object.values(value).every((val) => typeof val === "string");
}

function getMessages(): OssrI18n {
    const messages = window.ossrI18n;

    if (!isRecordStringString(messages)) {
        return {};
    }

    return messages;
}

export function t(key: string): string {
    const messages = getMessages();

    return messages[key] ?? key;
}

export function format(message: string, ...values: string[]): string {
    let next = 0;
    return message.replace(/%(?:(\d+)\$)?s/g, (placeholder, position) => {
        const index = position === undefined ? next++ : Number(position) - 1;
        return values[index] ?? placeholder;
    });
}
