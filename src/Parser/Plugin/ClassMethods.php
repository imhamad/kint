<?php

class Kint_Parser_Plugin_ClassMethods extends Kint_Parser_Plugin
{
    private static $cache = array();

    public function parse(&$var, Kint_Object &$o)
    {
        if ($o->type !== 'object' || !($o instanceof Kint_Object_Instance)) {
            return;
        }

        // Recursion or depth limit
        if (array_intersect($o->hints, array('recursion', 'depth_limit'))) {
            return;
        }

        $class = get_class($var);

        // assuming class definition will not change inside one request
        if (!isset(self::$cache[$class])) {
            $methods = array();

            $reflection = new ReflectionClass($class);

            foreach ($reflection->getMethods() as $method) {
                $methods[] = new Kint_Object_Method($method);
            }

            usort($methods, array('Kint_Parser_Plugin_ClassMethods', 'sort'));

            self::$cache[$class] = $methods;
        }

        if (!empty(self::$cache[$class])) {
            $rep = new Kint_Object_Representation('Available methods', 'methods');

            // Can't cache access paths
            foreach (self::$cache[$class] as $m) {
                $method = clone $m;
                $method->depth = $o->depth + 1;

                if (!$this->parser->childHasPath($o, $method)) {
                    $method->access_path = null;
                } else {
                    $method->setAccessPathFrom($o, $class);
                }

                $rep->contents[] = $method;
            }

            $o->addRepresentation($rep);
        }
    }

    private static function sort(Kint_Object_Method $a, Kint_Object_Method $b)
    {
        $sort = ((int) $a->static) - ((int) $b->static);
        if ($sort) {
            return $sort;
        }

        $sort = Kint_Object::sortByAccess($a, $b);
        if ($sort) {
            return $sort;
        }

        return Kint_Object_Instance::sortByHierarchy($a->owner_class, $b->owner_class);
    }
}
