export type UserRole = 'user' | 'admin'

export interface Profile {
  id: string
  username: string
  role: UserRole
  created_at: string
}

export interface ProjectLinks {
  github?: string
  youtube?: string
  drive?: string
}

export interface Project {
  id: number
  name: string
  description: string
  images: string[]
  tags: string[]
  links: ProjectLinks
  created_at: string
  updated_at: string
}

export interface ProjectInput {
  name: string
  description: string
  tags: string[]
  links: ProjectLinks
  images: string[]
}

export interface ContactMessage {
  id: number
  name: string
  email: string
  subject: string
  message: string
  read: boolean
  created_at: string
}

export interface AdminActivity {
  id: number
  actor_id: string | null
  action: string
  entity_type: string
  entity_id: string | null
  details: Record<string, unknown>
  created_at: string
}
