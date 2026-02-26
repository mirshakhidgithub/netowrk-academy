<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import { VNodeRenderer } from '@layouts/components/VNodeRenderer'
import { themeConfig } from '@themeConfig'
import authV2MaskDark from '@images/pages/misc-mask-dark.png'
import authV2MaskLight from '@images/pages/misc-mask-light.png'

const authThemeMask = useGenerateImageVariant(authV2MaskLight, authV2MaskDark)

definePage({
  meta: {
    layout: 'blank',
    unauthenticatedOnly: true,
  },
})

const router = useRouter()
const route = useRoute()

const refVForm = ref<InstanceType<typeof VForm>>()
const isLoading = ref(false)
const isSending = ref(false)
const errors = ref<Record<string, string | undefined>>({})
const successMessage = ref('')

const form = ref({
  email: (route.query.email as string) || '',
  code: '',
})

const sendCode = async () => {
  isSending.value = true
  errors.value = {}
  successMessage.value = ''

  try {
    await $api('/auth/verify-email/send', {
      method: 'POST',
      body: {
        email: form.value.email,
      },
      onResponseError({ response }) {
        errors.value = response._data?.errors ?? { email: ['Failed to send code'] }
      },
    })

    successMessage.value = 'Verification code sent. Check your email!'
  }
  catch (error) {
    console.error(error)
  }
  finally {
    isSending.value = false
  }
}

const verifyEmail = async () => {
  isLoading.value = true
  errors.value = {}

  try {
    await $api('/auth/verify-email/verify', {
      method: 'POST',
      body: {
        email: form.value.email,
        code: form.value.code,
      },
      onResponseError({ response }) {
        errors.value = response._data?.errors ?? { code: ['Invalid code'] }
      },
    })

    successMessage.value = 'Email verified successfully! Redirecting to login...'
    
    setTimeout(() => {
      router.push({ name: 'login', query: { verified: 'true' } })
    }, 1500)
  }
  catch (error) {
    console.error(error)
  }
  finally {
    isLoading.value = false
  }
}

const onSubmit = () => {
  refVForm.value?.validate().then(({ valid: isValid }) => {
    if (isValid)
      verifyEmail()
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
    no-gutters
    class="auth-wrapper bg-surface"
  >
    <VCol
      md="8"
      class="d-none d-md-flex"
    >
      <div class="position-relative bg-background w-100 me-0">
        <div
          class="d-flex align-center justify-center w-100 h-100"
          style="padding-inline: 100px;"
        >
          <div class="text-center">
            <VIcon
              icon="tabler-mail-check"
              size="120"
              class="mb-4"
              color="primary"
            />
            <h2 class="text-h4 mb-2">Check your email</h2>
            <p class="text-body-2">
              We've sent a verification code to your inbox
            </p>
          </div>
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
      class="auth-card-v2 d-flex align-center justify-center"
      style="background-color: rgb(var(--v-theme-surface));"
    >
      <VCard
        flat
        :max-width="500"
        class="mt-12 mt-sm-0 pa-4"
      >
        <VCardText>
          <h4 class="text-h4 mb-1">
            Verify your email ✉️
          </h4>
          <p class="mb-0">
            Enter the 6-digit code we sent to {{ form.email }}
          </p>
        </VCardText>

        <VCardText>
          <!-- Success Message -->
          <VAlert
            v-if="successMessage"
            type="success"
            class="mb-4"
            closable
          >
            {{ successMessage }}
          </VAlert>

          <VForm
            ref="refVForm"
            @submit.prevent="onSubmit"
          >
            <VRow>
              <!-- Email (for reference) -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.email"
                  :rules="[requiredValidator, emailValidator]"
                  label="Email Address"
                  type="email"
                  readonly
                  :error-messages="errors.email"
                />
              </VCol>

              <!-- Verification Code -->
              <VCol cols="12">
                <AppTextField
                  v-model="form.code"
                  :rules="[requiredValidator]"
                  label="Verification Code"
                  placeholder="000000"
                  maxlength="6"
                  class="text-center"
                  :error-messages="errors.code"
                />
              </VCol>

              <!-- Verify Button -->
              <VCol cols="12">
                <VBtn
                  block
                  type="submit"
                  :loading="isLoading"
                  :disabled="isSending"
                >
                  Verify Email
                </VBtn>
              </VCol>

              <!-- Resend Code -->
              <VCol
                cols="12"
                class="text-center text-base"
              >
                <span class="d-inline-block">Didn't receive the code?</span>
                <VBtn
                  variant="text"
                  size="small"
                  :loading="isSending"
                  @click="sendCode"
                >
                  Resend
                </VBtn>
              </VCol>

              <!-- Back to Login -->
              <VCol
                cols="12"
                class="text-center"
              >
                <RouterLink
                  class="text-primary"
                  :to="{ name: 'login' }"
                >
                  Back to login
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
@use "@core-scss/template/pages/page-auth";
</style>
