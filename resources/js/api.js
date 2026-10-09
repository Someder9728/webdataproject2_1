/* Same-Origin JSON API Client */
async function request(url, options = {}) {
    const response = await fetch(url, {
        ...options,
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            ...(options.headers || {}),
        },
    });

    const data = await response.json();

    if (!response.ok) {
        throw {
            status: response.status,
            data: data,
        };
    }

    return data;
}

/* Room API */
export const roomApi = {
    async getAll(search = "", page = 1, perPage = 20) {
        const params = new URLSearchParams({
            page: page,
            per_page: perPage,
        });

        if (search) {
            params.set("search", search);
        }

        const result = await request(`/api/v1/rooms?${params.toString()}`);
        return { ...result, pagination: result.meta ?? result.pagination };
    },

    async get(id) {
        return request(`/api/v1/rooms/${id}`);
    },

    async create(room) {
        return request("/api/v1/rooms", {
            method: "POST",
            body: JSON.stringify(room),
        });
    },

    async update(id, room) {
        return request(`/api/v1/rooms/${id}`, {
            method: "PATCH",
            body: JSON.stringify(room),
        });
    },

    async delete(id) {
        return request(`/api/v1/rooms/${id}`, {
            method: "DELETE",
        });
    },
};

/* Tenant API */
export const tenantApi = {
    async getAll(search = "", page = 1, perPage = 20) {
        const params = new URLSearchParams({
            page: page,
            per_page: perPage,
        });

        if (search) {
            params.set("search", search);
        }

        const result = await request(`/api/v1/tenants?${params.toString()}`);
        return { ...result, pagination: result.meta ?? result.pagination };
    },

    async get(id) {
        return request(`/api/v1/tenants/${id}`);
    },

    async create(tenant) {
        return request("/api/v1/tenants", {
            method: "POST",
            body: JSON.stringify(tenant),
        });
    },

    async update(id, tenant) {
        return request(`/api/v1/tenants/${id}`, {
            method: "PATCH",
            body: JSON.stringify(tenant),
        });
    },

    async delete(id) {
        return request(`/api/v1/tenants/${id}`, {
            method: "DELETE",
        });
    },

};

/* Dashboard API */
export const dashboardApi = {
    async get(month = "") {
        const params = new URLSearchParams();

        if (month) {
            params.set("month", month);
        }

        const query = params.toString();

        return request(`/api/v1/dashboard${query ? `?${query}` : ""}`);
    },
};
