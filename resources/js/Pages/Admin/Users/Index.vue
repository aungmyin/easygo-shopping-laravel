<template>
  <AdminLayout>
    <div class="p-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-8">Customers</h1>

      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Email</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Phone</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Joined</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ user.name }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ user.email }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ user.phone || '—' }}</td>
                <td class="px-6 py-4 text-sm">
                  <button
                    @click="toggleActive(user.id, user.is_active)"
                    :class="[
                      'px-2 py-1 rounded text-xs font-medium cursor-pointer',
                      user.is_active
                        ? 'bg-green-100 text-green-800 hover:bg-green-200'
                        : 'bg-gray-100 text-gray-800 hover:bg-gray-200',
                    ]"
                  >
                    {{ user.is_active ? 'Active' : 'Inactive' }}
                  </button>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(user.created_at) }}</td>
                <td class="px-6 py-4 text-sm text-gray-600">
                  —
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="users.meta.last_page > 1" class="mt-6 flex justify-center space-x-2">
        <Link
          v-if="users.links.prev"
          :href="users.links.prev"
          class="px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50"
        >
          Previous
        </Link>
        <span class="px-3 py-2">
          Page {{ users.meta.current_page }} of {{ users.meta.last_page }}
        </span>
        <Link
          v-if="users.links.next"
          :href="users.links.next"
          class="px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50"
        >
          Next
        </Link>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import type { PaginatedResponse, User } from '@/types';

interface Props {
  users: PaginatedResponse<User>;
}

defineProps<Props>();

const formatDate = (date: string) => {
  return new Intl.DateTimeFormat('en-US').format(new Date(date));
};

const toggleActive = (userId: number, currentStatus: boolean) => {
  router.post(`/admin/users/${userId}/toggle`, {});
};
</script>
