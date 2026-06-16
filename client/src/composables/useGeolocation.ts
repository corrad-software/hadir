import { computed, onMounted, ref } from "vue";

export type GeolocationCoords = { latitude: number; longitude: number };

export type GeolocationResult =
  | ({ ok: true } & GeolocationCoords)
  | { ok: false; error: string; code?: number };

export function geolocationErrorMessage(code: number): string {
  const messages: Record<number, string> = {
    1: "Location permission denied. Click the lock icon in the address bar → Site settings → Location → Allow, then reload this page.",
    2: "Location unavailable. Turn on Location Services on your device, or try from a mobile phone with GPS.",
    3: "Location request timed out. Move near a window or check your internet connection, then try again.",
  };

  return messages[code] ?? "Could not get GPS location.";
}

export function useGeolocation() {
  const position = ref<GeolocationCoords | null>(null);
  const loading = ref(false);
  const lastError = ref<string | null>(null);
  const permissionState = ref<PermissionState | "unsupported" | "unknown">("unknown");

  async function checkPermission() {
    if (!navigator.permissions?.query) {
      permissionState.value = "unknown";
      return;
    }

    try {
      const result = await navigator.permissions.query({ name: "geolocation" });
      permissionState.value = result.state;
      result.onchange = () => {
        permissionState.value = result.state;
      };
    } catch {
      permissionState.value = "unknown";
    }
  }

  function requestPosition(options?: { maximumAge?: number }): Promise<GeolocationResult> {
    return new Promise((resolve) => {
      if (!navigator.geolocation) {
        const error = "Your browser does not support GPS/location.";
        lastError.value = error;
        resolve({ ok: false, error });
        return;
      }

      loading.value = true;
      lastError.value = null;

      navigator.geolocation.getCurrentPosition(
        (pos) => {
          const coords = {
            latitude: pos.coords.latitude,
            longitude: pos.coords.longitude,
          };
          position.value = coords;
          loading.value = false;
          lastError.value = null;
          resolve({ ok: true, ...coords });
        },
        (err) => {
          const error = geolocationErrorMessage(err.code);
          lastError.value = error;
          loading.value = false;
          resolve({ ok: false, error, code: err.code });
        },
        {
          enableHighAccuracy: false,
          timeout: 20000,
          maximumAge: options?.maximumAge ?? 60000,
        },
      );
    });
  }

  async function prefetch() {
    if (position.value) {
      return { ok: true as const, ...position.value };
    }

    return requestPosition();
  }

  const isReady = computed(() => position.value !== null);
  const isDenied = computed(() => permissionState.value === "denied" || lastError.value?.includes("permission denied"));

  onMounted(() => {
    void checkPermission();
  });

  return {
    position,
    loading,
    lastError,
    permissionState,
    isReady,
    isDenied,
    requestPosition,
    prefetch,
    checkPermission,
  };
}
