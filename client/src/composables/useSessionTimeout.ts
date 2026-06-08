import { onBeforeUnmount, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { getMe } from "@/api/auth";

const IDLE_MS = 28 * 60 * 1000;  // warn after 28 min idle
const COUNTDOWN_S = 120;           // 2 min to respond before forced logout

export function useSessionTimeout() {
  const router = useRouter();
  const auth = useAuthStore();

  const showWarning = ref(false);
  const countdown = ref(COUNTDOWN_S);

  let idleTimer: number | null = null;
  let countdownTimer: number | null = null;

  function clearIdle() {
    if (idleTimer !== null) { window.clearTimeout(idleTimer); idleTimer = null; }
  }

  function clearCountdown() {
    if (countdownTimer !== null) { window.clearInterval(countdownTimer); countdownTimer = null; }
  }

  function resetIdle() {
    if (showWarning.value) return;
    clearIdle();
    idleTimer = window.setTimeout(startWarning, IDLE_MS);
  }

  function startWarning() {
    showWarning.value = true;
    countdown.value = COUNTDOWN_S;
    countdownTimer = window.setInterval(() => {
      countdown.value--;
      if (countdown.value <= 0) forceLogout();
    }, 1000);
  }

  async function staySignedIn() {
    clearCountdown();
    showWarning.value = false;
    try { await getMe(); } catch { /* session refresh — ignore errors */ }
    resetIdle();
  }

  async function forceLogout() {
    clearCountdown();
    clearIdle();
    showWarning.value = false;
    try { await auth.signOut(); } catch { /* ignore */ }
    router.push("/admin/login");
  }

  const EVENTS = ["mousemove", "keydown", "click", "scroll", "touchstart"] as const;

  onMounted(() => {
    EVENTS.forEach((e) => window.addEventListener(e, resetIdle, { passive: true }));
    resetIdle();
  });

  onBeforeUnmount(() => {
    EVENTS.forEach((e) => window.removeEventListener(e, resetIdle));
    clearIdle();
    clearCountdown();
  });

  return { showWarning, countdown, staySignedIn, forceLogout };
}
