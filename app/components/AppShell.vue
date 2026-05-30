<template>
  <div class="min-h-screen bg-gray-950 text-white flex flex-col">
    <!-- Header -->
    <header class="h-14 border-b border-gray-800 bg-gray-900 flex items-center px-4 justify-between shrink-0">
      <div class="flex items-center gap-2">
        <UIcon
          name="i-heroicons-cube-transparent"
          class="w-6 h-6 text-primary-500"
        />
        <span class="font-bold text-lg">MUH5 Shell</span>
      </div>
      <div class="flex items-center gap-2">
        <!-- Avatar placeholder -->
        <UAvatar
          src="https://avatars.githubusercontent.com/u/739984?v=4"
          alt="Player"
          size="sm"
        />
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto">
      <slot />
    </main>

    <!-- Footer -->
    <footer class="border-t border-gray-800 py-4 text-center text-gray-500 text-sm shrink-0">
      &copy; 2026 CCGame MUH5. Đã đăng ký bản quyền.
    </footer>
  </div>
</template>
