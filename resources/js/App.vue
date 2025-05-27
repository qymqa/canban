<template>
  <div id="app">
    <router-view />
  </div>
</template>

<script>
import { useAuthStore } from './stores/auth';

export default {
  name: 'App',
  setup() {
    const authStore = useAuthStore();
    
    // Initialize auth on app start
    authStore.initializeAuth();
    
    return {};
  },
};
</script>

<style>
body {
  margin: 0;
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Oxygen',
    'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue',
    sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  box-sizing: border-box;
}

#app {
  min-height: 100vh;
  background-color: #f5f5f5;
  width: 100%;
  direction: ltr;
}

/* Global styles */
*, *:before, *:after {
  box-sizing: inherit;
  -webkit-tap-highlight-color: transparent;
  -moz-tap-highlight-color: transparent;
}
</style> 