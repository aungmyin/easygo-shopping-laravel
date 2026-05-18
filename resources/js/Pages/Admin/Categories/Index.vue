<template>
  <AdminLayout>
    <div class="p-8">
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Categories</h1>
        <Link href="/admin/categories/create" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
          Add Category
        </Link>
      </div>

      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Parent</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Products</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="category in categories" :key="category.id" class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                  <span v-if="category.parent_id" class="text-gray-400 mr-2">└─</span>
                  {{ category.name }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">
                  {{ parentName(category.parent_id) || '—' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">{{ category.products_count || 0 }}</td>
                <td class="px-6 py-4 text-sm">
                  <span v-if="category.is_active" class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">
                    Active
                  </span>
                  <span v-else class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">
                    Inactive
                  </span>
                </td>
                <td class="px-6 py-4 text-sm space-x-2">
                  <Link
                    :href="`/admin/categories/${category.id}/edit`"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Edit
                  </Link>
                  <button
                    @click="confirmDelete(category.id)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import type { Category } from '@/types';

interface Props {
  categories: Category[];
}

const props = defineProps<Props>();

const parentName = (parentId?: number) => {
  if (!parentId) return null;
  return props.categories.find(c => c.id === parentId)?.name;
};

const confirmDelete = (id: number) => {
  if (confirm('Are you sure you want to delete this category?')) {
    router.delete(`/admin/categories/${id}`);
  }
};
</script>
