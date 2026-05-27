<template>
  <div class="space-y-6">
    <FeatureLocked
      title="Giftcode Redemption"
      description="Giftcode redemption is currently locked in this mock environment. No API is called."
      icon="i-heroicons-gift"
    />

    <div class="pt-6 border-t border-gray-800 opacity-50 pointer-events-none">
      <p class="text-sm text-gray-400 mb-2">
        Preview Layout (Disabled)
      </p>
      <div class="flex gap-2">
        <UInput
          placeholder="Enter giftcode..."
          class="flex-1"
        />
        <UButton
          color="primary"
          disabled
        >
          Redeem locked
        </UButton>
      </div>
    </div>
  </div>
</template>
