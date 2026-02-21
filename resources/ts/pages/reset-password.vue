<script setup lang="ts">
import { useGenerateImageVariant } from '@core/composable/useGenerateImageVariant'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import { VForm } from 'vuetify/components/VForm'
import type { AuthErrors } from '@/types/auth'
import authV2ForgotPasswordIllustrationDark from '@images/pages/auth-v2-forgot-password-illustration-dark.png'
import authV2ForgotPasswordIllustrationLight from '@images/pages/auth-v2-forgot-password-illustration-light.png'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'

const authThemeImg = useGenerateImageVariant(authV2ForgotPasswordIllustrationLight, authV2ForgotPasswordIllustrationDark)
const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const router = useRouter()
const route = useRoute()
const ability = useAbility()

const refVForm = ref<InstanceType<typeof VForm>>()
const isPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const isLoading = ref(false)
const isPasswordReset = ref(false)
const errors = ref<AuthErrors>({})

const form = ref({
  token: (route.query.token as string) ?? '',
  email: (route.query.email as string) ?? '',
  password: '',
  password_confirmation: '',
})

const resetPassword = async () => {
  isLoading.value = true
  errors.value = {}
  try {
    await $api('/auth/reset-password', {
      method: 'POST',
      body: {
        token: form.value.token,
        email: form.value.email,
        password: form.value.password,
        password_confirmation: form.value.password_confirmation,
      },
      onResponseError({ response }) {
        errors.value = response._data?.errors ?? {}
      },
    })
    isPasswordReset.value = true
    setTimeout(() => router.replace({ name: 'login' }), 3000)
  }
  catch (err) {
    console.error(err)
  }
  finally {
    isLoading.value = false
  }
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid)
      resetPassword()
  })
}
</script>

<template>
  <RouterLink to="/">
    <div class="auth-logo d-flex align-center gap-x-3">
      <VNodeRenderer :nodes="themeConfig.app.logo" />
      <h1 class="auth-title">
        {{ themeConfig.app.title }}
      </h1>
    </div>
  </RouterLink>

  <VRow
    class="auth-wrapper bg-surface"
    no-gutters
  >
    <VCol
      md="8"
      class="d-none d-md-flex"
    >
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 150px;"
        >
          <VImg
            max-width="468"
            :src="authThemeImg"
            class="auth-illustration mt-16 mb-2"
          />
        </div>

        <img
          class="auth-footer-mask"
          :src="authThemeMask"
          alt="auth-footer-mask"
          height="280"
          width="100"
        >
      </div>
    </VCol>

    <VCol
      cols="12"
      md="4"
      class="d-flex align-center justify-center"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 mt-sm-0 pa-4"
      >
        <VCardText>
          <h4 class="text-h4 mb-1">
            Reset Password 🔑
          </h4>
          <p class="mb-0">
            Enter your new password below
          </p>
        </VCardText>

        <VCardText>
          <VAlert
            v-if="isPasswordReset"
            type="success"
            variant="tonal"
            class="mb-4"
          >
            Password reset successfully! Redirecting to login...
          </VAlert>

          <VForm
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- Email (readonly, pre-filled from query) -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.email"
                  label="Email"
                  type="email"
                  readonly
                  :rules="[requiredValidator, emailValidator]"
                  :error-messages="errors.email"
                />
              </VCol>

              <!-- New password -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.password"
                  autofocus
                  label="New Password"
                  placeholder="············"
                  :type="isPasswordVisible ? 'text' : 'password'"
                  autocomplete="new-password"
                  :rules="[requiredValidator, passwordValidator]"
                  :error-messages="errors.password"
                  :append-inner-icon="isPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isPasswordVisible = !isPasswordVisible"
                />
              </VCol>

              <!-- Confirm password -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.password_confirmation"
                  label="Confirm Password"
                  placeholder="············"
                  :type="isConfirmPasswordVisible ? 'text' : 'password'"
                  autocomplete="new-password"
                  :rules="[requiredValidator, confirmedValidator(form.password_confirmation, form.password)]"
                  :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
                  @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
                />
              </VCol>

              <!-- Submit -->
              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                  :loading="isLoading"
                  :disabled="isPasswordReset"
                >
                  Set New Password
                </VBtn>
              </VCol>

              <!-- Back to login -->
              <VCol cols="12">
                <RouterLink
                  class="d-flex align-center justify-center"
                  :to="{ name: 'login' }"
                >
                  <VIcon
                    icon="tabler-chevron-left"
                    size="20"
                    class="me-1 flip-in-rtl"
                  />
                  <span>Back to login</span>
                </RouterLink>
              </VCol>
            </VRow>
          </VForm>
        </VCardText>
      </VCard>
    </VCol>
  </VRow>
</template>

<style lang="scss">
@use "@core-scss/template/pages/page-auth.scss";
</style>
