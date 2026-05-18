export interface User {
  id: number;
  name: string;
  email: string;
  phone?: string;
  role: 'admin' | 'customer';
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface Category {
  id: number;
  name: string;
  slug: string;
  description?: string;
  parent_id?: number;
  is_active: boolean;
  sort_order: number;
  products_count?: number;
  children?: Category[];
  created_at: string;
  updated_at: string;
}

export interface ProductImage {
  id: number;
  product_id: number;
  image_path: string;
  sort_order: number;
  url: string;
  created_at: string;
  updated_at: string;
}

export interface Product {
  id: number;
  name: string;
  slug: string;
  category_id: number;
  category?: Category;
  price: number;
  sale_price?: number;
  effective_price: number;
  discount_percent?: number;
  is_on_sale: boolean;
  stock: number;
  description?: string;
  short_description?: string;
  thumbnail?: string;
  is_active: boolean;
  is_featured: boolean;
  is_delivery_friendly: boolean;
  images?: ProductImage[];
  created_at: string;
  updated_at: string;
  deleted_at?: string;
}

export interface OrderItem {
  id: number;
  order_id: number;
  product_id: number;
  product?: Product;
  quantity: number;
  price: number;
  created_at: string;
  updated_at: string;
}

export interface Order {
  id: number;
  user_id: number;
  user?: User;
  order_number: string;
  status: 'pending' | 'confirmed' | 'processing' | 'shipped' | 'delivered' | 'cancelled';
  payment_status: 'pending' | 'paid';
  items?: OrderItem[];
  subtotal: number;
  delivery_fee: number;
  total: number;
  notes?: string;
  created_at: string;
  updated_at: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    from: number;
    last_page: number;
    per_page: number;
    to: number;
    total: number;
  };
  links: {
    first: string;
    last: string;
    prev?: string;
    next?: string;
  };
}

export interface DashboardStats {
  total_orders: number;
  pending_orders: number;
  revenue: number;
  total_products: number;
  low_stock: number;
  total_customers: number;
}

export interface FormErrors {
  [key: string]: string[];
}
