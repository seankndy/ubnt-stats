<?php
namespace SeanKndy\UbntStats;

use phpseclib\Net\SSH2;

class UbntDevice
{
    protected $ip;
    protected $ssh;
    protected $status; // output from status.cgi
    protected $config; // /tmp/running.cfg parsed into array

    public function __construct($ip) {
        $this->ip = $ip;
    }

    public function getIp() {
        return $this->ip;
    }

    public function setIp($ip) {
        $this->ip = $ip;
        return $this;
    }

    public function getHostname() {
        return $this->status->host->hostname;
    }

    public function getVersion() {
        return preg_replace('/[^0-9\.]/', '', $this->status->host->fwversion);
    }

    public function getDevModel() {
        return $this->status->host->devmodel;
    }

    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }

    public function getStations() {
        if (!isset($this->status->wireless->sta)) {
            $stas = $this->runCmd('/usr/www/sta.cgi');
            if (is_array($stas)) {
                $this->status->wireless->sta = $stas;
            } else {
                return null;
            }
        }
        $stations = [];
        foreach ($this->status->wireless->sta as $sta) {
            $stations[] = (array)$sta;
        }
        return $stations;
    }

    public function getLanInfo() {
        if (isset($this->status->lan)) {
            if (isset($this->status->lan->status[0]->plugged)) {
                $lan = $this->status->lan->status[0]->plugged ? ($this->status->lan->status[0]->speed . ($this->status->lan->status[0]->duplex ? 'Full' : 'Half')) : 'Unplugged';
            } else if (isset($this->status->lan->status[0])) {
                $lan = $this->status->lan->status[0];
            }
        } else if (isset($this->status->interfaces)) {
            foreach ($this->status->interfaces as $iface) {
                if ($iface->ifname == 'eth0') {
                    $lan = $iface->status->plugged ? ($iface->status->speed . ($iface->status->duplex ? 'Full' : 'Half')) : 'Unplugged';
                    break;
                }
            }
        } else {
            $lan = 'Unplugged';
        }
        return $lan;
    }

    public function getEssid() {
        return $this->status->wireless->essid;
    }

    public function getMode() {
        return strstr($this->status->wireless->mode, 'sta') ? 'Station' : 'Access Point';
    }

    public function getWds() {
        return isset($this->status->wireless->wds) ? $this->status->wireless->wds : '';
    }

    public function getUptime() {
        return sprintf('%.2f', $this->status->host->uptime / 86400) . ' days';
    }

    public function getFreq() {
        return preg_replace('/[^0-9]+/', '', $this->status->wireless->frequency);
    }

    public function getWidth() {
        return isset($this->status->wireless->chanbw) && $this->status->wireless->chanbw ? $this->status->wireless->chanbw : $this->status->wireless->chwidth;
    }

    public function getDistance() {
        return $this->status->wireless->distance;
    }

    public function getSignal() {
        if (isset($this->status->wireless->signal)) {
            return $this->status->wireless->signal;
        } else if (isset($this->status->sta) && $this->getMode() == 'Station') {
            return $this->status->sta[0]->signal;
        }
        return '';
    }

    public function getNoise() {
        return $this->status->wireless->noisef;
    }

    public function getNumAssoc() {
        return $this->status->wireless->count;
    }

    public function getWpaKey() {
        if ($config = $this->getConfig()) {
            return $config['wpasupplicant.profile.1.network.1.psk'];
        }
        return '';
    }

    public function getConfig() {
        if ($this->config) {
            return $this->config;
        }
        $output = $this->runCmd('cat /tmp/running.cfg', false);
        $this->config = [];
        foreach (preg_split('/[\r\n]+/', $output) as $line) {
            if (strstr($line, '=')) {
                list($k,$v) = explode('=', $line);
                $this->config[$k] = $v;
            }
        }
        return $this->config;
    }

    public function ssh($username, $password) {
        $this->ssh = new SSH2($this->ip, 22, 5);
        if ($this->ssh->login($username, $password)) {
            return true;
        } else {
            $this->ssh = null;
            return false;
        }
    }

    public function runCmd($cmd, $jsonOutput = true) {
        if ($this->ssh) {
            $output = $this->ssh->exec($cmd);
            if ($jsonOutput) {
                if (stristr($output, 'content-type')) {
                    list(,$json) = preg_split('/[\r\n]{2}/', $output, 2);
                } else {
                    $json = trim($output);
                }
                return json_decode($json);
            }
            return $output;
        }
        return null;
    }

    public static function init($ip, array $logins) {
        $device = new UbntDevice($ip);
        foreach ($logins as $login) {
            if ($device->ssh($login['user'], $login['pass'])) {
                $device->setStatus($device->runCmd('/usr/www/status.cgi'));

                return $device;
            }
        }
        return null;
    }
}
