# async-demo
异步Http请求demo
以为是依靠php的ticks机制实现,所以要在全局加上一个
```php
declare(ticks = 1);
```
