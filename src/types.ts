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

export type AwardType = 'winner' | 'runner_up_1' | 'runner_up_2' | 'gold' | 'silver' | 'bronze' | 'finalist' | 'other'

export interface Project {
  id: number
  slug: string | null
  name: string
  description: string
  images: string[]
  tags: string[]
  links: ProjectLinks
  award_type: AwardType | null
  award_title: string | null
  award_rank: number | null
  created_at: string
  updated_at: string
}

export interface ProjectInput {
  slug: string
  name: string
  description: string
  tags: string[]
  links: ProjectLinks
  images: string[]
  award_type: AwardType | null
  award_title: string | null
  award_rank: number | null
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
