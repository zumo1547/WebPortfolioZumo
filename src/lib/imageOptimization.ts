const WEBP_MIME = 'image/webp'
const SUPPORTED_IMAGE_TYPES = new Set(['image/jpeg', 'image/png', WEBP_MIME])

export const MAX_PROJECT_IMAGE_BYTES = 20 * 1024 * 1024
export const PROJECT_IMAGE_ACCEPT = 'image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp'

type OptimizeOptions = {
  maxWidth?: number
  maxHeight?: number
  quality?: number
}

const loadImage = (file: File) => new Promise<{ image: HTMLImageElement; objectUrl: string }>((resolve, reject) => {
  const objectUrl = URL.createObjectURL(file)
  const image = new Image()
  image.decoding = 'async'
  image.onload = () => resolve({ image, objectUrl })
  image.onerror = () => {
    URL.revokeObjectURL(objectUrl)
    reject(new Error(`ไม่สามารถอ่านไฟล์ ${file.name} ได้`))
  }
  image.src = objectUrl
})

const canvasToWebP = (canvas: HTMLCanvasElement, quality: number) => new Promise<Blob>((resolve, reject) => {
  canvas.toBlob((blob) => {
    if (!blob || blob.type !== WEBP_MIME) {
      reject(new Error('เบราว์เซอร์นี้ไม่รองรับการแปลงรูปเป็น WebP'))
      return
    }
    resolve(blob)
  }, WEBP_MIME, quality)
})

export const optimizeProjectImage = async (
  file: File,
  { maxWidth = 1920, maxHeight = 1920, quality = 0.82 }: OptimizeOptions = {},
) => {
  if (!SUPPORTED_IMAGE_TYPES.has(file.type)) throw new Error(`${file.name}: รองรับเฉพาะ JPG, PNG และ WebP`)
  if (file.size <= 0 || file.size > MAX_PROJECT_IMAGE_BYTES) throw new Error(`${file.name}: ไฟล์ต้องมีขนาดไม่เกิน 20 MB`)

  const { image, objectUrl } = await loadImage(file)
  try {
    const scale = Math.min(1, maxWidth / image.naturalWidth, maxHeight / image.naturalHeight)
    const width = Math.max(1, Math.round(image.naturalWidth * scale))
    const height = Math.max(1, Math.round(image.naturalHeight * scale))
    const canvas = document.createElement('canvas')
    canvas.width = width
    canvas.height = height
    const context = canvas.getContext('2d', { alpha: true })
    if (!context) throw new Error('ไม่สามารถเตรียมรูปภาพสำหรับอัปโหลดได้')

    context.imageSmoothingEnabled = true
    context.imageSmoothingQuality = 'high'
    context.drawImage(image, 0, 0, width, height)
    const webp = await canvasToWebP(canvas, quality)
    const output = file.type === WEBP_MIME && scale === 1 && webp.size >= file.size ? file : webp
    const name = `${file.name.replace(/\.[^.]+$/, '').replace(/[^a-zA-Z0-9._-]+/g, '-') || 'project-image'}.webp`

    return new File([output], name, { type: WEBP_MIME, lastModified: Date.now() })
  } finally {
    URL.revokeObjectURL(objectUrl)
  }
}
