import { roomApi, tenantApi, dashboardApi } from './api.js';

console.log('JSON API Client loaded');

window.roomApi = roomApi;
window.tenantApi = tenantApi;
window.dashboardApi = dashboardApi;