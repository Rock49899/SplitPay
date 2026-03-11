/**
 * Session Timeout and Auto-Logout Middleware
 * 
 * Handles automatic logout when session expires or user is inactive
 */

import axios from 'axios';
import router from '@/router';

let inactivityTimer = null;
let warningTimer = null;
const INACTIVITY_TIMEOUT = 30 * 60 * 1000; // 30 minutes in milliseconds
const WARNING_TIME = 2 * 60 * 1000; // 2 minutes before logout

export function initSessionTimeout() {
  // Reset timers on user activity
  const resetTimers = () => {
    clearTimeout(inactivityTimer);
    clearTimeout(warningTimer);

    // Show warning 2 minutes before logout
    warningTimer = setTimeout(() => {
      const shouldContinue = confirm(
        'Your session will expire in 2 minutes due to inactivity. Would you like to stay logged in?'
      );
      
      if (shouldContinue) {
        // Ping server to keep session alive
        axios.get('/api/ping').catch(() => {});
        resetTimers();
      } else {
        // Logout immediately
        logout();
      }
    }, INACTIVITY_TIMEOUT - WARNING_TIME);

    // Auto-logout after full timeout
    inactivityTimer = setTimeout(() => {
      logout();
    }, INACTIVITY_TIMEOUT);
  };

  // Listen for user activity
  const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
  events.forEach(event => {
    document.addEventListener(event, resetTimers, true);
  });

  // Start timers
  resetTimers();
}

export function clearSessionTimeout() {
  clearTimeout(inactivityTimer);
  clearTimeout(warningTimer);
  
  const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
  events.forEach(event => {
    document.removeEventListener(event, initSessionTimeout, true);
  });
}

function logout() {
  // Clear local data
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  
  // Redirect to login
  router.push({
    name: 'SignIn',
    query: { reason: 'session_expired' }
  });
  
  // Show message
  alert('Your session has expired due to inactivity. Please log in again.');
}

// Setup axios interceptor for 401 responses
export function setupAuthInterceptor() {
  axios.interceptors.response.use(
    (response) => response,
    (error) => {
      if (error.response?.status === 401) {
        // Session expired or unauthorized
        logout();
      }
      return Promise.reject(error);
    }
  );
}
