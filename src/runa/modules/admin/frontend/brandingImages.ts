export class BrandingImageError extends Error {}

/** Decode locally and send bounded PNG rasters; original files never reach storage. */
export async function prepareBrandingImage(
  file: File,
  kind: 'logo' | 'favicon',
) {
  if (
    !['image/png', 'image/jpeg', 'image/webp'].includes(file.type) ||
    file.size > 4 * 1024 * 1024
  )
    throw new BrandingImageError(
      'Choose a PNG, JPEG or WebP image up to 4 MiB.',
    );
  const src = URL.createObjectURL(file);
  const image = new Image();
  let timer: ReturnType<typeof setTimeout> | undefined;
  try {
    await new Promise<void>((resolve, reject) => {
      timer = setTimeout(
        () => reject(new BrandingImageError('The image could not be read.')),
        5000,
      );
      image.onload = () => resolve();
      image.onerror = () =>
        reject(new BrandingImageError('The image could not be read.'));
      image.src = src;
    });
    if (
      !image.naturalWidth ||
      !image.naturalHeight ||
      Math.max(image.naturalWidth, image.naturalHeight) > 4096
    )
      throw new BrandingImageError(
        'Image dimensions must not exceed 4096 pixels.',
      );
    function png(icon: boolean): string {
      const canvas = document.createElement('canvas');
      const scale = Math.min(
        1,
        1024 / Math.max(image.naturalWidth, image.naturalHeight),
      );
      canvas.width = icon
        ? 64
        : Math.max(1, Math.round(image.naturalWidth * scale));
      canvas.height = icon
        ? 64
        : Math.max(1, Math.round(image.naturalHeight * scale));
      const context = canvas.getContext('2d');
      if (!context)
        throw new BrandingImageError('The image could not be read.');
      const fit = Math.min(
        canvas.width / image.naturalWidth,
        canvas.height / image.naturalHeight,
      );
      const width = image.naturalWidth * fit,
        height = image.naturalHeight * fit;
      context.drawImage(
        image,
        (canvas.width - width) / 2,
        (canvas.height - height) / 2,
        width,
        height,
      );
      const encoded = canvas.toDataURL('image/png').split(',')[1]!;
      if (encoded.length > Math.ceil((icon ? 32768 : 524288) / 3) * 4)
        throw new BrandingImageError(
          'The prepared image is too large. Choose a simpler image.',
        );
      return encoded;
    }
    return kind === 'logo'
      ? { logo: png(false), logo_icon: png(true) }
      : { favicon: png(true) };
  } finally {
    if (timer) clearTimeout(timer);
    image.onload = null;
    image.onerror = null;
    image.src = '';
    URL.revokeObjectURL(src);
  }
}
