<script setup lang="ts">
import { VForm } from 'vuetify/components/VForm'
import type { AuthErrors } from '@/types/auth'

definePage({
  meta: {
    layout: 'blank',
    requiresAuth: true,
  },
})

const refVForm = ref<InstanceType<typeof VForm>>()
const isLoading = ref(false)
const isCurrentPasswordVisible = ref(false)
const isNewPasswordVisible = ref(false)
const isConfirmPasswordVisible = ref(false)
const errors = ref<AuthErrors>({})
const successMessage = ref('')

const form = ref({
  current_password: '',
  password: '',
  password_confirmation: '',
})

const changePassword = async () => {
  isLoading.value = true
  errors.value = {}
  successMessage.value = ''
  try {
    await $api('/auth/change-password', {
      method: 'POST',
      body: {
        current_password: form.value.current_password,
        password: form.value.password,
        password_confirmation: form.value.password_confirmation,
      },
      onResponseError({ response }) {
        errors.value = response._data?.errors ?? {}
      },
    })
    successMessage.value = 'Password changed successfully!'
    form.value = { current_password: '', password: '', password_confirmation: '' }
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
      changePassword()
  })
}
</script>

<template>
  <VCard>
    <VCardItem>
      <VCardTitle>Change Password</VCardTitle>
      <VCardSubtitle>Ensure your account uses a strong password</VCardSubtitle>
    </VCardItem>

    <VCardText>
      <VAlert
        v-if="successMessage"
        type="success"
        variant="tonal"
        class="mb-4"
        closable
        @click:close="successMessage = ''"
      >
        {{ successMessage }}
      </VAlert>

      <VForm
        ref="refVForm"
        @submit.prevent="onSubmit"
      >
        <VRow>
          <!-- Current password -->
          <VCol
            cols="12"
            md="6"
          >
            <AppTextField
              v-model="form.current_password"
              label="Current Password"
              placeholder="············"
              :type="isCurrentPasswordVisible ? 'text' : 'password'"
              autocomplete="current-password"
              :rules="[requiredValidator]"
              :error-messages="errors.current_password"
              :append-inner-icon="isCurrentPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
              @click:append-inner="isCurrentPasswordVisible = !isCurrentPasswordVisible"
            />
          </VCol>

          <VCol cols="12" />

          <!-- New password -->
          <VCol
            cols="12"
            md="6"
          >
            <AppTextField
              v-model="form.password"
              label="New Password"
              placeholder="············"
              :type="isNewPasswordVisible ? 'text' : 'password'"
              autocomplete="new-password"
              :rules="[requiredValidator, passwordValidator]"
              :error-messages="errors.password"
              :append-inner-icon="isNewPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
              @click:append-inner="isNewPasswordVisible = !isNewPasswordVisible"
            />
          </VCol>

          <!-- Confirm new password -->
          <VCol
            cols="12"
            md="6"
          >
            <AppTextField
              v-model="form.password_confirmation"
              label="Confirm New Password"
              placeholder="············"
              :type="isConfirmPasswordVisible ? 'text' : 'password'"
              autocomplete="new-password"
              :rules="[requiredValidator, confirmedValidator(form.password_confirmation, form.password)]"
              :append-inner-icon="isConfirmPasswordVisible ? 'tabler-eye-off' : 'tabler-eye'"
              @click:append-inner="isConfirmPasswordVisible = !isConfirmPasswordVisible"
            />
          </VCol>

          <!-- Actions -->
          <VCol cols="12">
            <VBtn
              type="submit"
              :loading="isLoading"
              class="me-3"
            >
              Save Changes
            </VBtn>
            <VBtn
              type="reset"
              variant="tonal"
              color="secondary"
              @click="form = { current_password: '', password: '', password_confirmation: '' }; errors = {}; successMessage = ''"
            >
              Reset
            </VBtn>
          </VCol>
        </VRow>
      </VForm>
    </VCardText>
  </VCard>
</template>
