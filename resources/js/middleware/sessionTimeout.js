import api, { applyToken } from '@/services/api';
import router from '@/router';

const TOKEN_KEY = 'api_token';
const USER_KEY = 'user';
const LAST_ACTIVITY_KEY = 'session_last_activity_at';
const STARTED_AT_KEY = 'session_started_at';

const IDLE_TIMEOUT_MS = Number(import.meta.env.VITE_IDLE_TIMEOUT_MINUTES || 30) * 60 * 1000;
const MAX_SESSION_AGE_MS = Number(import.meta.env.VITE_SESSION_MAX_AGE_MINUTES || 720) * 60 * 1000;

let checkInterval = null;
let listenersBound = false;
let interceptorRegistered = false;

const ACTIVITY_EVENTS = ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart', 'click'];

function nowMs() {
  return Date.now();
}

function getToken() {
  return localStorage.getItem(TOKEN_KEY);
}

function setSessionTimestampsIfMissing() {
  const now = String(nowMs());
  if (!localStorage.getItem(STARTED_AT_KEY)) localStorage.setItem(STARTED_AT_KEY, now);
  if (!localStorage.getItem(LAST_ACTIVITY_KEY)) localStorage.setItem(LAST_ACTIVITY_KEY, now);
}

function touchActivity() {
  if (!getToken()) return;
  localStorage.setItem(LAST_ACTIVITY_KEY, String(nowMs()));
}

function clearLocalAuthState() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(USER_KEY);
  localStorage.removeItem(LAST_ACTIVITY_KEY);
  localStorage.removeItem(STARTED_AT_KEY);
  applyToken(null);
}

function logout(reason = 'session_expired') {
  clearLocalAuthState();
  const currentName = router.currentRoute.value?.name;
  if (currentName !== 'Signin') {
    router.push({ name: 'Signin', query: { reason } });
  }
}

export function isSessionStillValid() {
  const token = getToken();
  if (!token) return false;

  const startedAt = Number(localStorage.getItem(STARTED_AT_KEY) || 0);
  const lastActivity = Number(localStorage.getItem(LAST_ACTIVITY_KEY) || 0);
  const now = nowMs();

  if (!startedAt || !lastActivity) return false;
  if ((now - startedAt) > MAX_SESSION_AGE_MS) return false;
  if ((now - lastActivity) > IDLE_TIMEOUT_MS) return false;

  return true;
}

function checkSessionValidity() {
  const token = getToken();
  if (!token) return;
  if (!isSessionStillValid()) logout('session_expired');
}

function bindActivityListeners() {
  if (listenersBound) return;
  ACTIVITY_EVENTS.forEach((event) => document.addEventListener(event, touchActivity, true));
  listenersBound = true;
}

function unbindActivityListeners() {
  if (!listenersBound) return;
  ACTIVITY_EVENTS.forEach((event) => document.removeEventListener(event, touchActivity, true));
  listenersBound = false;
}

export function initSessionTimeout() {
  if (!getToken()) return;
  setSessionTimestampsIfMissing();
  bindActivityListeners();
  checkSessionValidity();

  if (!checkInterval) {
    checkInterval = setInterval(checkSessionValidity, 60 * 1000);
  }
}

export function clearSessionTimeout() {
  if (checkInterval) {
    clearInterval(checkInterval);
    checkInterval = null;
  }
  unbindActivityListeners();
}

export function setupAuthInterceptor() {
  if (interceptorRegistered) return;
  interceptorRegistered = true;

  api.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error?.response?.status === 401 && getToken()) {
        logout('unauthorized');
      }
      return Promise.reject(error);
    }
  );
}
