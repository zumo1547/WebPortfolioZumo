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
