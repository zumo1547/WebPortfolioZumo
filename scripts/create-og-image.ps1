Add-Type -AssemblyName System.Drawing

$root = Split-Path -Parent $PSScriptRoot
$sourcePath = Join-Path $root 'public\assets\STUDENT_Wutthipat.png'
$outputPath = Join-Path $root 'public\assets\og-portfolio.png'

$bitmap = New-Object System.Drawing.Bitmap 1200, 630
$graphics = [System.Drawing.Graphics]::FromImage($bitmap)
$graphics.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
$graphics.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
$graphics.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::AntiAliasGridFit

$background = New-Object System.Drawing.Drawing2D.LinearGradientBrush(
  (New-Object System.Drawing.Point 0, 0),
  (New-Object System.Drawing.Point 1200, 630),
  ([System.Drawing.Color]::FromArgb(255, 7, 4, 15)),
  ([System.Drawing.Color]::FromArgb(255, 25, 10, 42))
)
$graphics.FillRectangle($background, 0, 0, 1200, 630)

$gridPen = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(20, 168, 85, 247)), 1
for ($x = 0; $x -le 1200; $x += 60) { $graphics.DrawLine($gridPen, $x, 0, $x, 630) }
for ($y = 0; $y -le 630; $y += 60) { $graphics.DrawLine($gridPen, 0, $y, 1200, $y) }

$glow = New-Object System.Drawing.Drawing2D.GraphicsPath
$glow.AddEllipse(720, -180, 620, 620)
$glowBrush = New-Object System.Drawing.Drawing2D.PathGradientBrush $glow
$glowBrush.CenterColor = [System.Drawing.Color]::FromArgb(105, 124, 58, 237)
$glowBrush.SurroundColors = @([System.Drawing.Color]::FromArgb(0, 124, 58, 237))
$graphics.FillPath($glowBrush, $glow)

$accentBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(255, 236, 72, 153))
$graphics.FillRectangle($accentBrush, 70, 70, 72, 6)

$labelFont = New-Object System.Drawing.Font 'Consolas', 17, ([System.Drawing.FontStyle]::Regular)
$nameFont = New-Object System.Drawing.Font 'Segoe UI', 59, ([System.Drawing.FontStyle]::Bold)
$roleFont = New-Object System.Drawing.Font 'Segoe UI', 25, ([System.Drawing.FontStyle]::Bold)
$bodyFont = New-Object System.Drawing.Font 'Segoe UI', 19, ([System.Drawing.FontStyle]::Regular)
$smallFont = New-Object System.Drawing.Font 'Consolas', 15, ([System.Drawing.FontStyle]::Regular)
$white = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(255, 245, 243, 250))
$muted = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(255, 172, 164, 188))
$purple = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(255, 192, 132, 252))
$blue = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(255, 56, 189, 248))

$graphics.DrawString('// PORTFOLIO 2026', $labelFont, $purple, 70, 94)
$graphics.DrawString('WUTTHIPHAT', $nameFont, $white, 64, 137)
$graphics.DrawString('SRIYANGNOK', $nameFont, $accentBrush, 64, 205)
$graphics.DrawString('CREATIVE DEVELOPER', $roleFont, $white, 70, 306)
$graphics.DrawString('GAME  /  IoT  /  ROBOTICS  /  AI  /  WEB', $bodyFont, $muted, 70, 354)

$tagPen = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(90, 168, 85, 247)), 1
$tagBrush = New-Object System.Drawing.SolidBrush ([System.Drawing.Color]::FromArgb(45, 168, 85, 247))
$graphics.FillRectangle($tagBrush, 70, 418, 473, 58)
$graphics.DrawRectangle($tagPen, 70, 418, 473, 58)
$graphics.DrawString('BUILD  |  TEST  |  IMPROVE', $smallFont, $purple, 92, 437)
$graphics.DrawString('webportfoliozumo.vercel.app', $smallFont, $muted, 70, 529)

$photo = [System.Drawing.Image]::FromFile($sourcePath)
$photoPath = New-Object System.Drawing.Drawing2D.GraphicsPath
$photoPath.AddArc(762, 52, 42, 42, 180, 90)
$photoPath.AddArc(1128, 52, 42, 42, 270, 90)
$photoPath.AddArc(1128, 536, 42, 42, 0, 90)
$photoPath.AddArc(762, 536, 42, 42, 90, 90)
$photoPath.CloseFigure()
$previousClip = $graphics.Clip
$graphics.SetClip($photoPath)
$destination = New-Object System.Drawing.Rectangle 762, 52, 408, 526
$source = New-Object System.Drawing.Rectangle 120, 70, 870, 1120
$graphics.DrawImage($photo, $destination, $source, [System.Drawing.GraphicsUnit]::Pixel)
$graphics.Clip = $previousClip
$photoBorder = New-Object System.Drawing.Pen ([System.Drawing.Color]::FromArgb(180, 192, 132, 252)), 2
$graphics.DrawPath($photoBorder, $photoPath)

$bitmap.Save($outputPath, [System.Drawing.Imaging.ImageFormat]::Png)

$photoBorder.Dispose(); $photoPath.Dispose(); $photo.Dispose(); $tagBrush.Dispose(); $tagPen.Dispose()
$blue.Dispose(); $purple.Dispose(); $muted.Dispose(); $white.Dispose(); $smallFont.Dispose(); $bodyFont.Dispose()
$roleFont.Dispose(); $nameFont.Dispose(); $labelFont.Dispose(); $accentBrush.Dispose(); $glowBrush.Dispose()
$glow.Dispose(); $gridPen.Dispose(); $background.Dispose(); $graphics.Dispose(); $bitmap.Dispose()

Write-Output "Created $outputPath"
