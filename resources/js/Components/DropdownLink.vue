<script setup>
import { Link } from '@inertiajs/vue3'

defineProps({
  href: { type: String, required: true },
  method: { type: String, default: 'get' },
  as: { type: String, default: 'a' },
})
</script>

<template>
  <Link
    :href="href"
    :method="method"
    :as="as"
    class="block w-full px-4 py-2 text-start text-sm text-gray-700 hover:bg-gray-100 focus:bg-gray-100"
  >
    <slot />
  </Link>
</template>
