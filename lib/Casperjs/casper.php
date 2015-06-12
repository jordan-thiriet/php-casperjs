<?php

namespace CasperJS;

/**
 *
 * CasperJS Wrapper
 *
 * Installation :
 *
 * npm install -g phantomjs
 * npm install -g casperjs
 *
 * @author jordan-thiriet
 *
 */

class Casper
{

    private $_script;       // {string} casperjs script
    private $_dateStart;    // {datetime} Datetime of run start
    private $_dateEnd;      // {datetime} Datetime of run stop
    private $_output;       // {array} Output formated in array of casperjs result
    private $_path;         // {string} Path for write script

    /**
     * Object Casper constructor
     * @param $width {integer} width of screen
     * @param $height {integer} height of screen
     * @param $path {string} path for write script casperjs
     */
    public function __construct($width, $height, $path)
    {
        $this->_path = $path;
        $this->_script .= "
            var writeOutput = function(scope, functionName, pass, element, value) {
                value = value === undefined ? null : value;
                scope.echo(scope.currentHTTPStatus+';'+scope.getCurrentUrl()+';'+functionName+';'+pass+';'+element+';'+value);
            };
            var x = require('casper').selectXPath;
            var error = 0;
            var count = 0;
            var casper = require('casper').create({
                viewportSize: {
                    width: $width,
                    height: $height
                }
            });";
    }

    /**
     * Set userAgent
     * @param $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->_path .= "casper.userAgent('$userAgent');";
    }

    /**
     * Set Authentication for .htpassword
     * @param $username {string}
     * @param $password {string}
     */
    public function setAuthentication($username, $password)
    {
        $this->_script .= "casper.options.pageSettings = {
                loadImages:true,
                loadPlugins:true,
                userName:'$username',
                password:'$password'
            };";
    }

    /**
     * Start casperjs script
     * @param $url {string} url start
     */
    public function start($url)
    {
        $this->_script .= "
            casper.start('$url', function() {
                writeOutput(this,'start',true, null, '$url');
            });";
    }

    /**
     * Click on a selector
     * @param $selector
     */
    public function click($selector)
    {
        $this->_script .= "
            casper.then(function() {
                if(casper.exists(x('$selector')))  {
                    this.thenClick(x('$selector'));
                    writeOutput(this,'click',true, '$selector');
                } else {
                    writeOutput(this,'click',false, '$selector');
                }
            });
        ";
    }

    /**
     * send keys into input
     * @param $selector
     */
    public function sendKeys($selector)
    {
        $this->_script .= "
            casper.then(function() {
                if(casper.exists(x('$selector')))  {
                    this.sendKeys(x('$selector'));
                    writeOutput(this,'sendKeys',true, '$selector');
                } else {
                    writeOutput(this,'click',false, '$selector');
                }
            });
        ";
    }

    /**
     * Set a variable
     * @param $selector
     * @param $name
     */
    public function setValue($selector, $name)
    {
        $selector = $this->replaceVariable($selector);
        $this->_script .= "
        var regex = /(<([^>]+)>)/ig
            ,body = this.fetchText(x('$selector'))
            ,$name = body.replace(regex, '');
            $name = $name.replace('\\n', '');
            $name = $name.replace('\\r', '');
        ";
    }

    /**
     * get a value from variable
     * @param $name
     */
    public function getValue($name)
    {
        $this->_script .= "
            writeOutput(this,'getValue',true, null ,$name);
        ";
    }

    /**
     * Open a different url
     * @param $url
     */
    public function open($url)
    {
        $this->_script .= "casper.thenOpen('$url',function() {
            writeOutput(this,'open',true);
        });";
    }

    /**
     * Return to previous page
     */
    public function back()
    {
        $this->_script .= "casper.then(function() {
            casper.back();
            writeOutput(this,'back',true);
        });";
    }

    /**
     * Go to forward page
     */
    public function forward()
    {
        $this->_script .= "casper.then(function() {
            casper.forward();
            writeOutput(this,'forward',true);
        });";
    }

    /**
     * Wait a give time
     * @param $time
     */
    public function wait($time)
    {
        $timems = $time * 1000;
        $this->_script .= "casper.then(function() {
            casper.wait($timems);
            writeOutput(this,'wait',true,null,'$time');
        });";
    }

    /**
     * Reload page
     */
    public function reload()
    {
        $this->_script = "this.reload(function() {
            writeOutput(this,'reload',true);
        });";
    }

    /**
     * Get capture from a selector
     *
     * @param $selector     selector xpath
     * @param null $path path of picture
     * @param string $format format of picture (png, jpeg,...)
     * @param int $quality quality of picture
     */
    public function captureSelector($selector, $path = null, $format = 'jpg', $quality = 70, $type = 'captureSelector')
    {
        $selector = $this->replaceVariable($selector);
        $path = $this->replaceVariable($path);
        $this->_script .= "casper.then(function() {
            if(casper.exists(x('$selector'))) {
                this.captureSelector('$path', x('$selector'),{
                        format: '$format',
                        quality: $quality
                });
                writeOutput(this,'$type',true,'$selector','$path');
            } else {
                writeOutput(this,'$type',false,'$selector','$path');
            }
        });";
    }

    /**
     * Get capture of page
     *
     * @param null $path
     * @param string $format
     * @param int $quality
     */
    public function capture($path = null, $format = 'jpg', $quality = 70)
    {
        $this->captureSelector('body', $path, $format, $quality, 'capture');
    }

    /**
     * start of loop for
     *
     * @param $start
     * @param $stop
     */
    public function forStart($start, $stop)
    {
        $this->_script .= "
        casper.then(function() {
            var range = [];
            for(var i=$start;i<=$stop;i++) {
                range.push(i);
            }
            var url = this.getCurrentUrl();
            casper.eachThen(range,function(response) {
                var i = response.data;
        ";
    }

    /**
     * end of loop for
     */
    public function forEnd()
    {
        $this->_script .= "
                casper.then(function() {
                    i++;
                });
               });
            });
        ";
    }

    /**
     * Count item of selector
     * @param $selector
     */
    public function count($selector, $var)
    {
        $this->_script .= "
        var $var = 0;
        casper.then(function() {
            if(casper.exists(x('$selector'))) {
                $var = this.getElementsInfo(x('$selector')).length;
                writeOutput(this,'reload',true, '$selector',$var);
            }
            });
        ";
    }

    /**
     * If selector exist
     * @param $selector
     */
    public function ifStart($selector)
    {
        $this->_script .= "
        casper.then(function() {
            if(casper.exists(x('$selector')))  {
        ";
    }

    /**
     * End of if
     */
    public function ifEnd()
    {
        $this->_script .= "
            }
        });
        ";
    }

    /**
     * Submit a form with data
     * @param $selector
     * @param array $data Json or array
     * @param bool $submit
     */
    public function fillForm($selector, $data = array(), $submit = false) {
        $jsonData = is_array($data) ? json_encode($data) : $data;
        $jsonSubmit = ($submit) ? 'true' : 'false';
        $this->_script .= "
            casper.then(function () {
                if(casper.exists(x('$selector')))  {
                    this.fillXPath('$selector', $jsonData, $jsonSubmit);
                    writeOutput(this,'fill_form',true,'$selector');
                } else {
                    writeOutput(this,'fill_form',false,'$selector');
                }
            });
        ";
    }

    /**
     * Check if value is equals to comparator
     *
     * @param $selector
     * @param $comparator
     */
    public function assertEquals($selector, $comparator)
    {
        $this->_script = "casper.then(function() {
            if(casper.exists(x('$selector')))  {
                if(this.getHTML(x('$selector')) == '$comparator') {
                    writeOutput(this,'assert_equals',true);
                } else {
                    writeOutput(this,'assert_equals',false,'not_equals');
                }
            } else {
                writeOutput(this,'assert_equals',false,'not_found');
            }
        });";
    }

    /**
     * Check if value contains comparator
     *
     * @param $selector
     * @param $comparator
     */
    public function assertContains($selector, $comparator)
    {
        $this->_script = "casper.then(function() {
            if(casper.exists(x('$selector')))  {
                if(this.getHTML(x('$selector')).match('$comparator')) {
                    writeOutput(this,'assert_equals',true);
                } else {
                    writeOutput(this,'assert_contains',false,'not_equals');
                }
            } else {
                writeOutput(this,'assert_contains',false,'not_found');
            }
        });";
    }

    /**
     * Check if element exist
     * @param $selector
     */
    public function assertIsExist($selector)
    {
        $this->_script = "if(casper.exists(x('$selector')))  {
                writeOutput(this,'assert_is_exist',true);
            } else {
                writeOutput(this,'assert_is_exist',false);
            }";
    }


    /**
     * Write script in file javascript and run this
     */
    public function run()
    {
        $this->_script .= "casper.run();";

        // Write in file javascript
        $handle = fopen($this->_path, 'w');
        fwrite($handle, $this->_script);

        // datetime start
        $this->_dateStart = new \DateTime;

        // Execute casperjs script
        exec('casperjs ' . $this->_path, $this->_output);

        // datetime end
        $this->_dateEnd = new \DateTime;

        // Delete file
        unlink($this->_path);

    }

    /**
     * Get output after run script
     * @return array
     */
    public function getOutput()
    {
        $results = array();
        $results['passed'] = true;
        
        // For all results of casperjs output
        foreach ($this->_output as $output) {
            $explode = explode(';', $output);
            $result = array();
            $result['http_status'] = $explode[0];
            $result['current_url'] = $explode[1];
            $result['function_name'] = $explode[2];
            $result['passed'] = $explode[3];
            if(!$result['passed']) {
                $results['passed'] = false;
            }
            $result['element'] = $explode[4];
            $result['value'] = $explode[5];
            $results['results'][] = $result;
        }
        $results['date_start'] = $this->_dateStart;
        $results['date_end'] = $this->_dateEnd;
        
        return $results;
    }

    /**
     * Replace variable for javascript
     * @param $string
     * @return mixed
     */
    private function replaceVariable($string)
    {
        $matches = array();
        preg_match_all('/{\w+}/', $string, $matches);
        foreach ($matches[0] as $match) {
            $string = str_replace($match, "'+" . substr($match, 1, -1) . "+'", $string);
        }
        return $string;
    }
}
