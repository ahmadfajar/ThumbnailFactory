# ThumbnailFactory
## PHP Class for Image Resizing and Cropping

In PHP world we know 3 extensions for image manipulation, ex: **gmagick, imagick and GD**. 
These extension have its own API and methods and sometimes different from each other.
And sometimes we face trouble, ex:
* In our developer machine we use imagick extension. After finish working then
  we upload to production server. But the server doesn't have imagick extension.
  It only has GD extension, and we can't or may not upload the extension.
  So, we have to change the code that uses imagick to GD. But if we don't know
  how to use GD extension,.. here comes the very trouble.
  
This class resolve the trouble, and make some adjustment. So we can use either gmagick, imagick or GD.
How to use the class:

```php
$thumbnailer = ThumbnailFactory::create("path/foto.jpg");
$thumbnailer->resize(192);
$thumbnailer->save("path/foto_Medium.png");
$thumbnailer->adaptiveResize(96, 96);
$thumbnailer->save("path/foto_Small.png");
```

Now, we have 2 images with different size than the original image.


