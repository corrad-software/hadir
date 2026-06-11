import { API_BASE_URL } from "@/env";

function getCsrfToken(): string {
  const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
  return match ? decodeURIComponent(match[1]) : "";
}

function buildHeaders(init?: HeadersInit) {
  const headers = new Headers(init ?? {});
  if (!headers.has("Content-Type") && !(init instanceof FormData)) {
    headers.set("Content-Type", "application/json");
  }
  const token = getCsrfToken();
  if (token) {
    headers.set("X-XSRF-TOKEN", token);
  }
  return headers;
}

export async function ensureCsrfCookie(): Promise<void> {
  if (!getCsrfToken()) {
    await fetch(`${API_BASE_URL}/sanctum/csrf-cookie`, {
      credentials: "include",
    });
  }
}

export async function apiRequest<T>(path: string, options: RequestInit = {}): Promise<T> {
  const isForm = options.body instanceof FormData;
  const headers = isForm ? new Headers(options.headers) : buildHeaders(options.headers);

  // Always include CSRF token, even for FormData uploads
  if (isForm) {
    const token = getCsrfToken();
    if (token) {
      headers.set("X-XSRF-TOKEN", token);
    }
  }

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    credentials: "include",
    headers,
  });

  const raw = await response.text();
  let payload: { data?: T; error?: { message?: string } };
  try {
    payload = raw ? JSON.parse(raw) : {};
  } catch {
    throw new Error(
      response.ok
        ? "Invalid JSON response from server"
        : "API routing error: server returned HTML instead of JSON. Check /api and /sanctum proxy to Laravel.",
    );
  }

  if (!response.ok) {
    throw new Error(payload?.error?.message || "Request failed");
  }

  return payload as T;
}
