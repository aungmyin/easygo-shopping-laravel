<template>
  <AdminLayout>
    <div class="p-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ category ? 'Edit Category' : 'Create Category' }}</h1>

      <form @submit.prevent="submit" class="bg-white rounded-lg shadow p-8 max-w-2xl">
        <!-- Name -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Category Name *</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          />
          <span v-if="form.errors.name" class="text-red-600 text-sm">{{ form.errors.name[0] }}</span>
        </div>

        <!-- Description -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
          <textarea
            v-model="form.description"
            rows="4"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          ></textarea>
          <span v-if="form.errors.description" class="text-red-600 text-sm">{{ form.errors.description[0] }}</span>
        </div>

        <!-- Parent Category -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Parent Category</label>
          <select
            v-model="form.parent_id"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          >
            <option :value="null">None (Top Level)</option>
            <option v-for="cat in parents" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
          <span v-if="form.errors.parent_id" class="text-red-600 text-sm">{{ form.errors.parent_id[0] }}</span>
        </div>

        <!-- Sort Order -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
          <input
            v-model="form.sort_order"
            type="number"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          />
          <span v-if="form.errors.sort_order" class="text-red-600 text-sm">{{ form.errors.sort_order[0] }}</span>
        </div>

        <!-- Active -->
        <div class="mb-6">
          <label class="flex items-center">
            <input v-model="form.is_active" type="checkbox" class="rounded" />
            <span class="ml-2 text-sm font-medium text-gray-700">Active</span>
          </label>
        </div>

        <!-- Submit Button -->
        <div class="flex space-x-4">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
          >
            {{ form.processing ? 'Saving...' : category ? 'Update' : 'Create' }}
          </button>
          <Link href="/admin/categories" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
            Cancel
          </Link>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import type { Category } from '@/types';

interface Props {
  category?: Category | null;
  parents: Category[];
}

const props = defineProps<Props>();

const form = useForm({
  name: props.category?.name || '',
  description: props.category?.description || '',
  parent_id: props.category?.parent_id || null,
  is_active: props.category?.is_active ?? true,
  sort_order: props.category?.sort_order || 0,
});

const submit = () => {
  if (props.category) {
    form.put(`/admin/categories/${props.category.id}`, {
      onSuccess: () => {
        window.location.href = '/admin/categories';
      },
    });
  } else {
    form.post('/admin/categories', {
      onSuccess: () => {
        window.location.href = '/admin/categories';
      },
    });
  }
};
</script>
