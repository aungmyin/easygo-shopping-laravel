<template>
  <AdminLayout>
    <div class="p-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-8">{{ product ? 'Edit Product' : 'Create Product' }}</h1>

      <form @submit.prevent="submit" class="bg-white rounded-lg shadow p-8 max-w-2xl">
        <!-- Name -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
          <input
            v-model="form.name"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          />
          <span v-if="form.errors.name" class="text-red-600 text-sm">{{ form.errors.name[0] }}</span>
        </div>

        <!-- Category -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
          <select
            v-model="form.category_id"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          >
            <option value="">Select a category</option>
            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
              {{ cat.name }}
            </option>
          </select>
          <span v-if="form.errors.category_id" class="text-red-600 text-sm">{{ form.errors.category_id[0] }}</span>
        </div>

        <!-- Price -->
        <div class="grid grid-cols-2 gap-6 mb-6">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Price (THB) *</label>
            <input
              v-model="form.price"
              type="number"
              step="0.01"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            />
            <span v-if="form.errors.price" class="text-red-600 text-sm">{{ form.errors.price[0] }}</span>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Sale Price (THB)</label>
            <input
              v-model="form.sale_price"
              type="number"
              step="0.01"
              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            />
            <span v-if="form.errors.sale_price" class="text-red-600 text-sm">{{ form.errors.sale_price[0] }}</span>
          </div>
        </div>

        <!-- Stock -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity *</label>
          <input
            v-model="form.stock"
            type="number"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          />
          <span v-if="form.errors.stock" class="text-red-600 text-sm">{{ form.errors.stock[0] }}</span>
        </div>

        <!-- Short Description -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Short Description</label>
          <textarea
            v-model="form.short_description"
            rows="2"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
          ></textarea>
          <span v-if="form.errors.short_description" class="text-red-600 text-sm">{{ form.errors.short_description[0] }}</span>
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

        <!-- Thumbnail -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Thumbnail</label>
          <div class="flex items-center space-x-4">
            <div
              v-if="product?.thumbnail || preview"
              class="w-20 h-20 rounded border border-gray-300 flex items-center justify-center overflow-hidden"
            >
              <img
                :src="preview || `/storage/${product?.thumbnail}`"
                alt="Thumbnail"
                class="w-full h-full object-cover"
              />
            </div>
            <input
              type="file"
              accept="image/*"
              @change="handleFileChange"
              class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
            />
          </div>
          <span v-if="form.errors.thumbnail" class="text-red-600 text-sm">{{ form.errors.thumbnail[0] }}</span>
        </div>

        <!-- Flags -->
        <div class="mb-6 space-y-3">
          <label class="flex items-center">
            <input v-model="form.is_active" type="checkbox" class="rounded" />
            <span class="ml-2 text-sm font-medium text-gray-700">Active</span>
          </label>
          <label class="flex items-center">
            <input v-model="form.is_featured" type="checkbox" class="rounded" />
            <span class="ml-2 text-sm font-medium text-gray-700">Featured</span>
          </label>
          <label class="flex items-center">
            <input v-model="form.is_delivery_friendly" type="checkbox" class="rounded" />
            <span class="ml-2 text-sm font-medium text-gray-700">Delivery Friendly</span>
          </label>
        </div>

        <!-- Submit Button -->
        <div class="flex space-x-4">
          <button
            type="submit"
            :disabled="form.processing"
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
          >
            {{ form.processing ? 'Saving...' : product ? 'Update' : 'Create' }}
          </button>
          <Link href="/admin/products" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
            Cancel
          </Link>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import type { Product, Category } from '@/types';

interface Props {
  product?: Product | null;
  categories: Category[];
}

const props = defineProps<Props>();
const preview = ref<string>('');

const form = useForm({
  name: props.product?.name || '',
  category_id: props.product?.category_id || '',
  price: props.product?.price || '',
  sale_price: props.product?.sale_price || '',
  stock: props.product?.stock || '',
  short_description: props.product?.short_description || '',
  description: props.product?.description || '',
  thumbnail: null as File | null,
  is_active: props.product?.is_active ?? true,
  is_featured: props.product?.is_featured ?? false,
  is_delivery_friendly: props.product?.is_delivery_friendly ?? false,
});

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  const file = target.files?.[0];
  if (file) {
    form.thumbnail = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      preview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
};

const submit = () => {
  if (props.product) {
    form.post(`/admin/products/${props.product.id}`, {
      onSuccess: () => {
        window.location.href = '/admin/products';
      },
    });
  } else {
    form.post('/admin/products', {
      onSuccess: () => {
        window.location.href = '/admin/products';
      },
    });
  }
};
</script>
