<?php
namespace Imi;

use Imi\Event\Event;
use Imi\Bean\Container;
use Imi\Util\Coroutine;

abstract class RequestContext
{
    /**
     * 上下文集合
     *
     * @var array
     */
    private static $context = [];

    /**
     * 为当前请求创建上下文，返回当前协程ID
     * 
     * @param array $data
     * @return int
     * @deprecated 1.0.17
     */
    public static function create(array $data = [])
    {
        return Coroutine::getuid();
    }

    /**
     * 销毁当前请求的上下文
     * @return void
     * @deprecated 1.0.17
     */
    public static function destroy()
    {
    }

    /**
     * 判断当前请求上下文是否存在
     * @return boolean
     * @deprecated 1.0.17
     */
    public static function exists()
    {
        return true;
    }
    
    /**
     * 销毁当前请求的上下文
     * @return void
     */
    private static function __destroy()
    {
        Event::trigger('IMI.REQUEST_CONTENT.DESTROY');
        $context = Coroutine::getContext();
        if(!$context)
        {
            $coId = Coroutine::getuid();
            if(isset(static::$context[$coId]))
            {
                unset(static::$context[$coId]);
            }
        }
    }

    /**
     * 获取上下文数据
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public static function get($name, $default = null)
    {
        $context = Coroutine::getContext();
        if($context)
        {
            return $context[$name] ?? $default;
        }
        return static::$context[Coroutine::getuid()][$name] ?? $default;
    }

    /**
     * 设置上下文数据
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public static function set($name, $value)
    {
        $context = Coroutine::getContext();
        if(!$context)
        {
            $coId = Coroutine::getuid();
            if(!isset(static::$context[$coId]))
            {
                static::$context[$coId] = [];
            }
            $context = &static::$context[$coId];
        }
        $context[$name] = $value;
        if(!($context['__bindDestroy'] ?? false))
        {
            $context['__bindDestroy'] = true;
            defer('static::__destroy');
        }
    }

    /**
     * 批量设置上下文数据
     *
     * @param array $data
     * @return void
     */
    public static function muiltiSet(array $data)
    {
        $context = Coroutine::getContext();
        if(!$context)
        {
            $coId = Coroutine::getuid();
            if(!isset(static::$context[$coId]))
            {
                static::$context[$coId] = [];
            }
            $context = &static::$context[$coId];
        }
        foreach($data as $k => $v)
        {
            $context[$k] = $v;
        }
        if(!($context['__bindDestroy'] ?? false))
        {
            $context['__bindDestroy'] = true;
            defer('static::__destroy');
        }
    }

    /**
     * 使用回调来使用当前请求上下文数据
     *
     * @param callable $callback
     * @return mixed
     */
    public static function use(callable $callback)
    {
        $context = Coroutine::getContext();
        if(!$context)
        {
            $coId = Coroutine::getuid();
            if(!isset(static::$context[$coId]))
            {
                static::$context[$coId] = [];
            }
            $context = &static::$context[$coId];
        }
        $result = $callback($context);
        if(!($context['__bindDestroy'] ?? false))
        {
            $context['__bindDestroy'] = true;
            defer('static::__destroy');
        }
        return $result;
    }

    /**
     * 获取当前上下文
     * @return array
     */
    public static function &getContext()
    {
        $context = Coroutine::getContext();
        if(!$context)
        {
            $coId = Coroutine::getuid();
            if(!isset(static::$context[$coId]))
            {
                static::$context[$coId] = [];
            }
            $context = &static::$context[$coId];
        }
        if(!($context['__bindDestroy'] ?? false))
        {
            $context['__bindDestroy'] = true;
            defer('static::__destroy');
        }
        return $context;
    }

    /**
     * 获取当前的服务器对象
     * @return \Imi\Server\Base|null
     */
    public static function getServer()
    {
        return static::get('server');
    }

    /**
     * 在当前服务器上下文中获取Bean对象
     * @param string $name
     * @return mixed
     */
    public static function getServerBean($name, ...$params)
    {
        return static::get('server')->getBean($name, ...$params);
    }

    /**
     * 在当前请求上下文中获取Bean对象
     * @param string $name
     * @return mixed
     */
    public static function getBean($name, ...$params)
    {
        $container = static::get('container');
        if(null === $container)
        {
            $container = new Container;
            static::set('container', $container);
        }
        return $container->get($name, ...$params);
    }

}
