<template>
  <AdminLayout>
    <div class="p-8">
      <div class="flex items-center justify-between mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Products</h1>
        <Link href="/admin/products/create" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
          Add Product
        </Link>
      </div>

      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Product</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Price</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Stock</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="product in products.data" :key="product.id" class="hover:bg-gray-50">
                <td class="px-6 py-4">
                  <div class="flex items-center">
                    <img
                      v-if="product.thumbnail"
                      :src="`/storage/${product.thumbnail}`"
                      :alt="product.name"
                      class="w-10 h-10 rounded mr-3 object-cover"
                    />
                    <div v-else class="w-10 h-10 rounded mr-3 bg-gray-200"></div>
                    <div>
                      <p class="font-medium text-gray-900">{{ product.name }}</p>
                      <p class="text-sm text-gray-600">{{ product.slug }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600">{{ product.category?.name || 'N/A' }}</td>
                <td class="px-6 py-4">
                  <div class="text-sm">
                    <p class="font-medium text-gray-900">{{ formatCurrency(product.effective_price) }}</p>
                    <p v-if="product.is_on_sale" class="text-red-600 line-through text-xs">{{ formatCurrency(product.price) }}</p>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm">
                  <span :class="['px-2 py-1 rounded text-xs font-medium', stock_status(product.stock)]">
                    {{ product.stock }} units
                  </span>
                </td>
                <td class="px-6 py-4 text-sm">
                  <span v-if="product.is_active" class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">
                    Active
                  </span>
                  <span v-else class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">
                    Inactive
                  </span>
                </td>
                <td class="px-6 py-4 text-sm space-x-2">
                  <Link
                    :href="`/admin/products/${product.id}/edit`"
                    class="text-blue-600 hover:text-blue-900"
                  >
                    Edit
                  </Link>
                  <button
                    @click="confirmDelete(product.id)"
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

      <!-- Pagination -->
      <div v-if="products.meta.last_page > 1" class="mt-6 flex justify-center space-x-2">
        <Link
          v-if="products.links.prev"
          :href="products.links.prev"
          class="px-3 py-2 bg-white border border-gray-300 rounded hover:bg-gray-50"
        >
          Previous
        </Link>
        <span class="px-3 py-2">
          Page {{ products.meta.current_page }} of {{ products.meta.last_page }}
        </span>
        <Link
          v-if="products.links.next"
          :href="products.links.next"
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
import type { PaginatedResponse, Product } from '@/types';

interface Props {
  products: PaginatedResponse<Product>;
}

defineProps<Props>();

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('th-TH', {
    style: 'currency',
    currency: 'THB',
  }).format(value);
};

const stock_status = (stock: number) => {
  if (stock < 10) return 'bg-red-100 text-red-800';
  if (stock < 50) return 'bg-yellow-100 text-yellow-800';
  return 'bg-green-100 text-green-800';
};

const confirmDelete = (id: number) => {
  if (confirm('Are you sure you want to delete this product?')) {
    router.delete(`/admin/products/${id}`);
  }
};
</script>
