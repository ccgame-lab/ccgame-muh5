export function apiSuccess<T>(data: T, message = 'Success') {
  return {
    success: true,
    message,
    data,
  }
}

export function apiError(message: string, code = 400) {
  throw createError({
    statusCode: code,
    message,
    data: {
      success: false,
      message,
    },
  })
}
