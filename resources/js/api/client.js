import axios from 'axios';

const api = axios.create({
    baseURL: '/api/v1',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
    withCredentials: true,
});

export const getCurrentUser = async () => {
    const response = await api.get('/me');
    return response.data;
};

export default api;