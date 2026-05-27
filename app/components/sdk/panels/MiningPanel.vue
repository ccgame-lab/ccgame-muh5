<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <h3 class="text-sm font-semibold text-gray-200">
        [Mock] Máy Đào / Monument
      </h3>
      <UBadge
        color="neutral"
        variant="solid"
        size="xs"
      >
        Dữ liệu mẫu
      </UBadge>
    </div>

    <!-- Monument Info Cards -->
    <div class="grid grid-cols-2 gap-4 opacity-50">
      <UCard class="bg-gray-900 border-gray-800 p-0 text-center py-3">
        <p class="text-xs text-gray-400 mb-1">
          Tốc độ đào
        </p>
        <p class="text-lg font-bold text-cyan-400">
          0 KC/h
        </p>
      </UCard>
      <UCard class="bg-gray-900 border-gray-800 p-0 text-center py-3">
        <p class="text-xs text-gray-400 mb-1">
          Máy hoạt động
        </p>
        <p class="text-lg font-bold text-gray-400">
          0 / 3
        </p>
      </UCard>
    </div>

    <!-- Feature Locked Message -->
    <FeatureLocked
      title="Máy đào Kim Cương đang niêm phong chuẩn bị S1"
      description="Hệ thống Máy Đào Kim Cương (Diamond Monument) hiện đang ở trạng thái niêm phong thử nghiệm (Read-Only) và chưa kết nối cơ sở dữ liệu thực tế."
      icon="i-heroicons-cpu-chip"
    />
  </div>
</template>
