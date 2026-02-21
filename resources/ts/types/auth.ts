export interface User {
  id: number
  name: string
  username: string | null
  email: string
  avatar: string | null
  email_verified_at: string | null
  created_at: string
  updated_at: string
}

export interface LoginCredentials {
  email: string
  password: string
}

export interface RegisterForm {
  name: string
  username: string
  email: string
  password: string
  password_confirmation: string
  privacyPolicies: boolean
}

export interface ForgotPasswordForm {
  email: string
}

export interface ResetPasswordForm {
  token: string
  email: string
  password: string
  password_confirmation: string
}

export interface ChangePasswordForm {
  current_password: string
  password: string
  password_confirmation: string
}

export interface AuthResponse {
  accessToken: string
  userData: User
  userAbilityRules: Array<{ action: string; subject: string }>
}

export interface AuthErrors {
  email?: string[]
  password?: string[]
  current_password?: string[]
  [key: string]: string[] | undefined
}
