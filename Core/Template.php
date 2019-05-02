<?php

namespace Core;

class Template
{
    protected $template;

    public function template($template)
    {
        $template = str_replace('\\', DS, $template);
        $template = '..'.DS._VIEW.$template.'.html';

        if (!is_file($template) || !is_readable($template)) {
            $this->exception("The template '${template}' not found.");
        }

        $this->template = $template;

        return $this;
    }

    public function render($template = null, $data = [], $return = false)
    {
        if (null !== $template) {
            $this->template($template);
        }

        $this->env = $GLOBALS['config']->env;

        if ('production' == $this->env) {
            $cache = new Cache();
            $contents = $cache->get('global'.$template.$_SERVER['REQUEST_URI']);
            if ($contents) {
                if ($return) {
                    return $contents;
                }
                echo $contents;
                exit;
            }
        }

        ob_start();
        include $this->template;
        $output = ob_get_contents();
        ob_end_clean();

        $parser = $this->_parser($output, $data);
        if ('production' == $this->env) {
            $cache->set('global'.$template.$_SERVER['REQUEST_URI'], $parser);
        }

        if ($return) {
            return $parser;
        }
        echo $parser;
    }

    public function exception($message)
    {
        $this->template('error/default');
        $this->render(null, ['error_message' => $message, 'date' => date('Y')]);
        exit;
    }

    private function _parser($template = null, $data)
    {
        if (null === $template) {
            return false;
        }

        //No need to support PHP tags in HTML Template
        $template = str_replace(['<?', '<?php', '?>'], ['&lt;?', '&lt;?php', '?&gt;'], $template);

        $replace = [];

        if ($data) {
            foreach ($data as $key => $val) {
                $parse = is_array($val) ? $this->_parseMatch($key, $val) : $this->_parse($key, (string) $val);
                $replace = array_merge($replace, $parse);
            }
        }

        unset($data);

        return strtr($template, $replace);
    }

    private function _parse($key, $val)
    {
        return ['{'.$key.'}' => (string) $val];
    }

    /**
     * Parses tags: {tag} string... {/tag}.
     *
     * @param string $var
     * @param array  $data
     * @param string $template
     * @param mixed  $string
     *
     * @return array
     */
    private function _parseMatch($var, $data)
    {
        $replace = [];

        preg_match_all(
            '#{\s*'.preg_quote($var).'\s*}(.+?){\s*'.'/'.preg_quote($var).'\s*}#s',
            $this->template,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $str = '';
            foreach ($data as $row) {
                $temp = [];
                foreach ($row as $key => $val) {
                    if (is_array($val)) {
                        $pair = $this->_parseMatch($key, $val, $match[1]);
                        if (!empty($pair)) {
                            $temp = array_merge($temp, $pair);
                        }

                        continue;
                    }

                    $temp['{'.$key.'}'] = $val;
                }

                $str .= strtr($match[1], $temp);
            }

            $replace['#'.$match[0].'#s'] = $str;
        }

        return $replace;
    }
}
