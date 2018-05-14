<?php

namespace Casperjs;

class Casper
{

    private $id;
    private $dateStart;
    private $dateEnd;
    private $_string;
    private $path;
    private $output;
    private $rand = null;
    private $varFor = array();
    private $currentVar = null;

    public function __construct($id, $path, $width, $height, $timeout, $username = null, $password = null)
    {
        $this->id = $id;
        $this->path = $path;
        $this->pathScript = $path . '/script/';
        /*Pour ajouter les asserts à casperjs*/
        $this->_string = 'phantom.casperTest = true;';
        $this->_string .= "var x = require('casper').selectXPath;var error = 0;";
        $this->_string .= "var casper = require('casper').create({
            viewportSize: {
                width: $width,
                height: $height
            },
            silentErrors: true
        });";
        if ($username !== null && $password !== null) {
            $this->_string .= "casper.options.pageSettings = {
                loadImages:true,
                loadPlugins:true,
                userName:'$username',
                password:'$password'
            };";
        }
    }

    public function start($url)
    {
        $this->_string .= "
            var compteur = 0;

            casper.start('" . $url . "', function() {
            var date = new Date();
            var pass = false;
            var count = 0;
            var url = null;
            if(this.currentHTTPStatus==200)
                pass = true;
                this.echo(pass+';'+this.currentHTTPStatus+';null;'+this.getCurrentUrl()+';null;start;'+date.toJSON()+';null');
            });";
        $this->captureError();
    }

    public function setUserAgent($userAgent)
    {
        $this->_string .= "casper.userAgent('$userAgent');";
    }

    public function startFor($value)
    {
        $value = explode(';', $value);
        if (count($value) == 3) {
            $this->varFor[] = $value[2];
        } else {
            $this->varFor[] = 'i';
        }
        $this->currentVar = end($this->varFor);
        $this->_string .= "
        casper.then(function() {
            var range = [];
            for(var $this->currentVar=$value[0];$this->currentVar<=$value[1];$this->currentVar++) {
                range.push($this->currentVar);
            }
            var url = this.getCurrentUrl();
            casper.eachThen(range,function(response) {
                var $this->currentVar = response.data;
                casper.thenOpen(url, function() {

        ";
    }

    public function finishFor($value)
    {
        if($value==='' || $value === null) {
            $value = 'i';
        }
        $this->_string .= "
                });
                casper.then(function() {
                    $value++;
                });
               });
            });
        ";
        array_pop($this->varFor);
        $this->currentVar = end($this->varFor);
    }

    public function startIf($selector)
    {
        $isContinueOp = strpos($selector, 'is_exist(');
        $isContinueOpNot = strpos($selector, '!is_exist(');
        if ($isContinueOp === 0) {
            $newSelector = str_replace('is_exist(', '', $selector);
            $newSelector = substr($newSelector, 0, -1);
            $newSelector = "casper.exists(x('$newSelector'))";
        } else if($isContinueOpNot === 0) {
            $newSelector = str_replace('!is_exist(', '', $selector);
            $newSelector = substr($newSelector, 0, -1);
            $newSelector = "!casper.exists(x('$newSelector'))";
        } else {
            $newSelector = $selector;
        }
        $this->_string .= "
        casper.then(function() {
            if($newSelector)  {
        ";
    }

    public function finishIf()
    {
        $this->_string .= "
            }
        });
        ";
    }

    public function count($selector, $value)
    {
        if ($value === '' || $value === null) {
            $value = 'count';
        }
        $this->_string .= "
        $value = 0;
        casper.then(function() {
            $value = this.getElementsInfo(x('$selector')).length;
            });
        ";
    }

    public function getUrl()
    {
        $this->_string .= "
        casper.then(function() {
            url = this.getCurrentUrl();
          });
        ";
    }

    public function storeValue($selector, $name)
    {
        $this->_string .= "
          $name = null;
          casper.then(function() {
            if(casper.exists(x('$selector'))) {
                $name = this.fetchText(x('$selector'));
            }
          });
        ";
    }

    public function compteurPP()
    {
        $this->_string .= "
          compteur++;
        ";
    }

    public function getAttribute($selector, $value) {

        $value = explode(';',$value);
        $name = $value[0];
        $attribute = $value[1];
        $this->_string .= "
          casper.then(function() {

          $name = this.evaluate(function(xpath) {
                return __utils__.getElementByXPath(xpath).getAttribute('$attribute');
            }, { xpath: '$selector' });
          });
        ";
    }

    public function click($selector)
    {
        $isContinueOp = strpos($selector, 'opt(');
        if ($isContinueOp === 0) {
            $newSelector = str_replace('opt(', '', $selector);
            $newSelector = substr($newSelector, 0, -1);
        } else {
            $newSelector = $selector;
        }
        $this->_string .= "casper.then(function() {
                var date = new Date();
                var xpath =false;
                casper.waitFor(function check() {
                    if(casper.exists('$newSelector'))  {
                        return true;
                    }
                    else if(casper.exists(x('$newSelector'))) {
                        xpath = true;
                        return true;
                    }
                    else {
                        return false;
                    }
                }, function then() {
                    if(xpath)
                        this.click(x('$newSelector'));
                    else
                        this.click('$newSelector');
                    this.echo('true;null;true;'+this.getCurrentUrl()+';$selector;click;'+date.toJSON()+';null');
                }, function timeout() {
                    if(error === 0) {
                        captureError();
                        error++;
                    }
                    this.echo('false;null;false;'+this.getCurrentUrl()+';$selector;click;'+date.toJSON()+';null');
                });
        });";
    }

    public function getValue($selector, $name)
    {
        $isContinueOp = strpos($selector, 'opt(');
        if ($isContinueOp === 0) {
            $newSelector = str_replace('opt(', '', $selector);
            $newSelector = substr($newSelector, 0, -1);
        } else {
            $newSelector = $selector;
        }
        $isVar = strpos($selector, 'var(');
        if ($isVar === 0) {
            $value = str_replace('var(', '', $selector);
            $value = substr($value, 0, -1);
            $exist = "$value !== undefined";
        } else {
            $value = "this.getHTML(x('$newSelector'))";
            $exist = "casper.exists(x('$newSelector'))";
        }
        $this->_string .= <<<FRAGMENT
casper.then(function() {
var date = new Date();
        casper.waitFor(function check() {
            if($exist) {
                return true;
            }
            else {
                return false;
            }
        }, function then() {
            var value = '';
            value = $value;
            value = value.replace(/(\\r\\n|\\n|\\r)/gm," ");
            
            this.echo('true;null;true;'+this.getCurrentUrl()+';$name;getValue;'+date.toJSON()+';'+value);
        }, function timeout() {
            if(error === 0) {
                captureError();
                error++;
            }
            this.echo('false;null;false;'+this.getCurrentUrl()+';$selector;getValue;'+date.toJSON()+';null');
        });
});

FRAGMENT;
    }

    public function sendKeys($selector, $text)
    {
        $isContinueOp = strpos($selector, 'opt(');
        if ($isContinueOp === 0) {
            $newSelector = str_replace('opt(', '', $selector);
            $newSelector = substr($newSelector, 0, -1);
        } else {
            $newSelector = $selector;
        }
        $this->_string .= "casper.then(function() {
                var date = new Date();
                var xpath =false;
                casper.waitFor(function check() {
                    if(casper.exists('$newSelector'))  {
                        return true;
                    }
                    else if(casper.exists(x('$newSelector'))) {
                        xpath = true;
                        return true;
                    }
                    else {
                        return false;
                    }
                }, function then() {
                    if(xpath)
                        this.sendKeys(x('$newSelector'), '$text');
                    else
                        this.sendKeys('$newSelector', '$text');
                    this.echo('true;null;true;'+this.getCurrentUrl()+';$selector;sendKeys;'+date.toJSON()+';$text')
                }, function timeout() {
                    if(error === 0) {
                        captureError();
                        error++;
                    }
                    this.echo('false;null;false;'+this.getCurrentUrl()+';$selector;sendKeys;'+date.toJSON()+';$text')
                });
        });";
    }

    public function open($url)
    {
        $this->_string .= "casper.thenOpen('$url',function() {
            var date = new Date();
            this.echo('true;null;true;'+this.getCurrentUrl()+';$url;open;'+date.toJSON()+';null');
        });";
    }

    public function back()
    {
        $this->_string .= "casper.then(function() {
            casper.back();
            var date = new Date();
            this.echo('true;null;true;'+this.getCurrentUrl()+';null;back;'+date.toJSON()+';null');
        });";
    }

    public function scrollToBottom()
    {
        $this->_string .= "casper.then(function() {
            casper.scrollToBottom();
            var date = new Date();
            this.echo('true;null;true;'+this.getCurrentUrl()+';null;scrollBottom;'+date.toJSON()+';null');
        });";
    }

    public function wait($time)
    {
        $timems = $time * 1000;
        $this->_string .= "casper.then(function() {
            casper.wait($timems);
            var date = new Date();
            this.echo('true;null;true;'+this.getCurrentUrl()+';null;wait;'+date.toJSON()+';$time');
        });";
    }

    public function waitPhantom($time)
    {
        $timems = $time * 1000;
        $this->_string .= "casper.then(function() {
            casper.wait($timems);
            var date = new Date();
            this.echo('true;null;true;'+this.getCurrentUrl()+';null;waitPhantom;'+date.toJSON()+';$time');
        });";
    }


    public function capture($path, $top = 0, $left = 0, $width = 0, $height = 0, $format = 'jpg', $quality = 100)
    {
        $this->_string .= "casper.then(function() {
            var date = new Date();
            this.echo('true;null;true;'+this.getCurrentUrl()+';null;capture;'+date.toJSON()+';$path');
            if(pass)
            {
                this.capture('$path', {
                        top: $top,
                        left: $left,
                        width: $width,
                        height: $height,
                        format: '$format',
                        quality: $quality
                    });
            }
        });";
    }

    public function captureError()
    {
        $pathCapture = $this->path . 'capture/' . $this->id . '/error.jpg';
        $this->_string .= "
        var captureError = function() {
        casper.then(function() {
            this.captureSelector('$pathCapture','body', {
                    format: 'jpg',
                    quality: '70'
                });
        });
        };";
    }

    public function captureSelector($selector, $path = null, $format = 'jpg', $quality = 70)
    {
        $isContinueOp = strpos($selector, 'opt(');
        if ($isContinueOp === 0) {
            $newSelector = str_replace('opt(', '', $selector);
            $newSelector = substr($newSelector, 0, -1);
        } else {
            $newSelector = $selector;
        }
        if ($path === null) {
            $arrayCapture = $this->getPathCapture($format);
            $nameCapture = $arrayCapture['name'];
            $pathCapture = $arrayCapture['path'];
        } else {
            $nameCapture = $path;
            $pathCapture = $path;
        }
        $this->_string .= "casper.then(function() {
            var date = new Date();
            var xpath =false;
            casper.waitFor(function check() {
                if(casper.exists('$newSelector'))  {
                    return true;
                }
                else if(casper.exists(x('$newSelector'))) {
                    xpath = true;
                    return true;
                }
                else {
                    return false;
                }
            }, function then() {
                if(xpath)
                {
                    this.captureSelector('$pathCapture',x('$newSelector'), {
                        format: '$format',
                        quality: $quality
                    });
                }
                else
                {
                    this.captureSelector('$pathCapture','$newSelector', {
                        format: '$format',
                        quality: $quality
                    });
                }
                this.echo('true;null;true;'+this.getCurrentUrl()+';$selector;captureSelector;'+date.toJSON()+';$nameCapture');
            }, function timeout() {
                if(error === 0) {
                    captureError();
                    error++;
                }
                this.echo('false;null;false;'+this.getCurrentUrl()+';$selector;captureSelector;'+date.toJSON()+';$nameCapture');
            });
        });";
    }


    public function form($selector, $jsonData, $jsonSubmit = true)
    {
        $isContinueOp = strpos($selector, 'opt(');
        if ($isContinueOp === 0) {
            $newSelector = str_replace('opt(', '', $selector);
            $newSelector = substr($newSelector, 0, -1);
        } else {
            $newSelector = $selector;
        }
        $this->_string .= "
        casper.then(function() {
            var date = new Date();
            var xpath =false;
            casper.waitFor(function check() {
                if(casper.exists('$newSelector'))  {
                    return true;
                }
                else if(casper.exists(x('$newSelector'))) {
                    xpath = true;
                    return true;
                }
                else {
                    return false;
                }
            }, function then() {
                if(xpath)
                {
                    pass = true;
                    try {
                        this.fill(x('$newSelector'),$jsonData, $jsonSubmit);
                    } catch (e) {
                        pass = false;
                    }
                }
                else
                {
                    pass = true;
                    try {
                        this.fill('$newSelector',$jsonData, $jsonSubmit);
                    } catch (e) {
                        pass = false;
                    }
                }
                if(pass === false && error === 0) {
                    captureError();
                    error++;
                }
                this.echo(pass+';null;true;'+this.getCurrentUrl()+';$selector;form;'+date.toJSON()+';$jsonData');
            }, function timeout() {
                if(error === 0) {
                    captureError();
                    error++;
                }
                this.echo('false;null;false;'+this.getCurrentUrl()+';$selector;form;'+date.toJSON()+';$jsonData');
            });
        });";
    }

    public function assertEquals($selector, $comparator)
    {
        $isContinueOp = strpos($selector, 'opt(');
        if ($isContinueOp === 0) {
            $newSelector = str_replace('opt(', '', $selector);
            $newSelector = substr($newSelector, 0, -1);
        } else {
            $newSelector = $selector;
        }
        $this->_string .= "casper.then(function() {
            var pass = false;
            var date = new Date();
            if(casper.exists('$newSelector'))  {
                if(this.getHTML('$newSelector') == '$comparator') {
                    pass = true;
                }
            } else if(casper.exists(x('$newSelector')))  {
                if(this.getHTML(x('$newSelector')) == '$comparator') {
                    pass = true;
                }
            }
            if(pass === false && error === 0) {
                captureError();
                error++;
            }
            this.echo(pass+';null;'+pass+';'+this.getCurrentUrl()+';$selector;isEquals;'+date.toJSON()+';$comparator');
        });";
    }

    public function assertContains($selector, $comparator)
    {
        $isContinueOp = strpos($selector, 'opt(');
        if ($isContinueOp === 0) {
            $newSelector = str_replace('opt(', '', $selector);
            $newSelector = substr($newSelector, 0, -1);
        } else {
            $newSelector = $selector;
        }
        $isRegex = strpos($comparator, 'regex(');
        if ($isRegex === 0) {
            $comparator = str_replace('regex(', '', $comparator);
            $comparator = substr($comparator, 0, -1);
            $this->_string .= "casper.then(function() {
                var pass = false;
                var date = new Date();
                if(casper.exists('$newSelector'))  {
                    if(this.getHTML('$newSelector').match('$comparator')) {
                        pass = true;
                    }
                } else if(casper.exists(x('$newSelector')))  {
                    if(this.getHTML(x('$newSelector')).match('$comparator')) {
                        pass = true;
                    }
                }
                if(pass === false && error === 0) {
                    captureError();
                    error++;
                }
                this.echo(pass+';null;'+pass+';'+this.getCurrentUrl()+';$selector;isContains;'+date.toJSON()+';$comparator');
            });";
        } else {
            $this->_string .= "casper.then(function() {
                var pass = false;
                var date = new Date();
                if(casper.exists('$newSelector'))  {
                    if(this.getHTML('$newSelector').indexOf('$comparator') > -1) {
                        pass = true;
                    }
                } else if(casper.exists(x('$newSelector')))  {
                    if(this.getHTML(x('$newSelector')).indexOf('$comparator') > -1) {
                        pass = true;
                    }
                }
                if(pass === false && error === 0) {
                    captureError();
                    error++;
                }
                this.echo(pass+';null;'+pass+';'+this.getCurrentUrl()+';$selector;isContains;'+date.toJSON()+';$comparator');
            });";
        }
    }

    public function assertIsExist($selector)
    {
        $isContinueOp = strpos($selector, 'opt(');
        if ($isContinueOp === 0) {
            $newSelector = str_replace('opt(', '', $selector);
            $newSelector = substr($newSelector, 0, -1);
        } else {
            $newSelector = $selector;
        }
        $this->_string .= "casper.then(function() {
            var pass = false;
            var date = new Date();
            if(casper.exists('$newSelector'))  {
                pass = true;
            } else if(casper.exists(x('$newSelector')))  {
                pass = true;
            }
            if(pass === false && error === 0) {
                captureError();
                error++;
            }
            this.echo(pass+';null;'+pass+';'+this.getCurrentUrl()+';$selector;isExist;'+date.toJSON()+';');
        });";
    }

    public function run()
    {
        $this->captureSelector('body');
        $this->captureSelector('body');
        $this->setVariables('compteur');
        $this->_string .= "casper.run();";
        $handle = fopen($this->getPathScript() . '.js', 'w');
        fwrite($handle, $this->_string);
        $this->dateStart = new \DateTime;
        exec('php -v', $this->output);
        var_dump($this->output);die();
        $this->dateEnd = new \DateTime;
    }


    private function getPathScript()
    {
        $pathScript = $this->path . 'script/';
        if (!is_dir($pathScript))
            mkdir($pathScript);
        return $pathScript . md5($this->id);
    }

    private function getPathCapture($format)
    {
        $date = new \DateTime;
        $pathCapture = $this->path . 'capture/';
        if (!is_dir($pathCapture))
            mkdir($pathCapture);
        $pathCapture = $pathCapture . $this->id . '/';
        if (!is_dir($pathCapture))
            mkdir($pathCapture);
        $rand = rand(0, 1000);
        while ($this->rand === $rand)
            $rand = rand(0, 1000);
        $this->rand = $rand;
        $nameCapture = md5($this->id . $date->getTimestamp()) . $this->rand . '.' . $format;
        return array('path' => $pathCapture . $nameCapture, 'name' => $nameCapture);
    }

    public function setVariables($variables) {
        $variables = explode(';',$variables);
        $string = '';
        foreach($variables as $variable) {
            $string .= "';$variable::'+$variable";
            if ($variable !== end($variables)) {
                $string .= "+";
            }
        }
        $this->_string .= "casper.then(function() {
            this.echo('variables'+$string);
        });";
    }

    public function getOutput()
    {
        $results = array();
        $pass = true;
        $numItems = count($this->output)-1;
        $i = 0;
        $lastDate = null;
        $nbPass = 0;
        $nbNotPass = 0;
        $nbNotPlay = 0;
        $phantomTime = 0;
        $play = true;
        foreach ($this->output as $output) {
            $explode = explode(';', $output);

            if ($explode[0] === 'variables') {
                for($j=1;$j<count($explode);$j++) {
                    $variable = explode('::',$explode[$j]);
                    $results['variables'][$variable[0]] = $variable[1];
                }
                $numItems--;
            } else {
                $result = array();
                $isContinueOp = strpos($explode[4], 'opt(');
                if ($isContinueOp === 0) {
                    $element = str_replace('opt(', '', $explode[4]);
                    $element = substr($element, 0, -1);
                    $result['pass'] = true;
                    $result['element'] = $element;
                } else {
                    $result['pass'] = $explode[0] === 'true' ? true : false;
                    $result['element'] = $explode[4];
                }
                $result['status'] = $explode[1];
                $result['exist'] = $explode[2];
                $result['url'] = $explode[3];
                $result['type'] = $explode[5];
                $result['date'] = new \DateTime($explode[6]);
                $result['play'] = $play;
                if ($lastDate !== null) {
                    $diff = $this->millisecsBetween($lastDate->getTimestamp(), $result['date']->getTimestamp());
                    $results['results'][$i - 1]['diff'] = $diff;
                }
                $result['value'] = $explode[7];
                if ($result['type'] === 'waitPhantom')
                    $phantomTime += $result['value'];
                $lastDate = $result['date'];
                /*Recupère le dernier element qui est la capture de fin*/
                if (++$i === $numItems)
                    $results['endCapture'] = $result['value'];
                else {
                    if ($result['type'] !== 'start') {
                        if (!$play)
                            $nbNotPlay++;
                        else if ($result['pass'])
                            $nbPass++;
                        else
                            $nbNotPass++;
                    }
                    $results['results'][] = $result;
                }
                if (!$result['pass'] && $play)
                    $play = false;
                if (!$result['pass'])
                    $pass = false;
            }

        }
        $results['pass'] = $pass;
        $results['nbPass'] = $nbPass;
        $results['nbNotPass'] = $nbNotPass;
        $results['nbNotPlay'] = $nbNotPlay;
        $results['totalTime'] = $this->dateStart->diff($this->dateEnd)->format('%i') * 60 + $this->dateStart->diff($this->dateEnd)->format('%s') - $phantomTime;
        return $results;
    }

    private function millisecsBetween($dateOne, $dateTwo, $abs = true)
    {
        $func = $abs ? 'abs' : 'intval';
        return $dateTwo - $dateOne;
    }
}
