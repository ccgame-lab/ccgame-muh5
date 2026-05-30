<template>
  <AppShell>
    <div class="max-w-md mx-auto p-6 text-center mt-20">
      <h1 class="text-4xl font-bold mb-4 text-transparent bg-clip-text bg-gradient-to-r from-primary-400 to-primary-600">
        MUH5 Shell
      </h1>
      <p class="text-gray-400 text-lg mb-8">
        Cổng vào thế giới game
      </p>

      <UCard class="bg-gray-900 border-gray-800">
        <UIcon
          name="i-heroicons-play"
          class="w-12 h-12 text-primary-500 mx-auto mb-4"
        />
        <h2 class="text-xl font-semibold mb-2">
          Sẵn sàng chơi?
        </h2>
        <p class="text-sm text-gray-400 mb-6">
          Khởi động vào thế giới game với SDK overlay tích hợp.
        </p>
        <UButton
          to="/play"
          color="primary"
          size="lg"
          block
        >
          Vào Game
        </UButton>
      </UCard>
    </div>
  </AppShell>
</template>
