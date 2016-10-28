<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class mpower extends eqLogic {
	/*     * *************************Attributs****************************** */
	public static $_widgetPossibility = array('custom' => true);

	public static function cron($_eqlogic_id = null) {
		if ($_eqlogic_id !== null) {
			$eqLogics = array(eqLogic::byId($_eqlogic_id));
		} else {
			$eqLogics = eqLogic::byType('mpower');
		}
		foreach ($eqLogics as $mpower) {
			if ($mpower->getIsEnable() == 1) {
				log::add('mpower', 'debug', 'Pull Cron pour mpower');
				$i = 0;
				$sessid = mt_rand(1, 9);
				do {
					$sessid .= mt_rand(0, 9);
				} while (++$i < 31);
				$ipmpower = $mpower->getConfiguration('addr');
				$user = $mpower->getConfiguration('user');
				$pwd = $mpower->getConfiguration('pwd');
				$cmd = 'curl -X POST -d "username=' . $user . '&password=' . $pwd . '" -b "AIROS_SESSIONID=' . $sessid . '" ' . $ipmpower . '/login.cgi';
				exec($cmd);
				log::add('mpower', 'debug', $cmd);
				$mpowerinfo = $mpower->getmpowerInfo($sessid);
			}
		}
		return;
	}

	public function getmpowerInfo($sessid) {
		try {
			$changed = false;
			$ipmpower = $this->getConfiguration('addr');
			$cmd = 'curl -b "AIROS_SESSIONID=' . $sessid . '" ' . $ipmpower . '/sensors';
			$mpowerinfo = exec($cmd);
			log::add('mpower', 'debug', $cmd);
			$jsonmpowerinfo = json_decode($mpowerinfo, true);
			log::add('mpower', 'debug', print_r($jsonmpowerinfo, true));
			$changed = false;
			foreach ($jsonmpowerinfo['sensors'] as $sensor) {
				if ($this->getConfiguration('model') != '6power' && $this->getConfiguration('model') != '3power') {
					$sensor = $sensor['state'];
				}
				$changed = $this->checkAndUpdateCmd('etat' . $sensor['port'], $sensor['output']) || $changed;
				$changed = $this->checkAndUpdateCmd('power' . $sensor['port'], round($sensor['power'], 2)) || $changed;
				$changed = $this->checkAndUpdateCmd('voltage' . $sensor['port'], round($sensor['voltage'], 2)) || $changed;
				$changed = $this->checkAndUpdateCmd('current' . $sensor['port'], round($sensor['current'], 2)) || $changed;
				$changed = $this->checkAndUpdateCmd('powerfactor' . $sensor['port'], round($sensor['powerfactor'], 2)) || $changed;
				$changed = $this->checkAndUpdateCmd('energy' . $sensor['port'], round($sensor['energy'] / 10, 2)) || $changed;
			}
			if ($changed) {
				$this->refreshWidget();
			}
		} catch (Exception $e) {

		}
		return;
	}

	public function postSave() {
		if (!$this->getId()) {
			return;
		}

		$etat1 = $this->getCmd(null, 'etat1');
		if (!is_object($etat1)) {
			$etat1 = new mpowerCmd();
			$etat1->setLogicalId('etat1');
			$etat1->setName(__('Etat 1', __FILE__));
		}
		$etat1->setType('info');
		$etat1->setIsVisible(0);
		$etat1->setDisplay('generic_type', 'ENERGY_STATE');
		$etat1->setSubType('binary');
		$etat1->setEqLogic_id($this->getId());
		$etat1->save();
		$etat1id = $etat1->getId();

		$power1 = $this->getCmd(null, 'power1');
		if (!is_object($power1)) {
			$power1 = new mpowerCmd();
			$power1->setLogicalId('power1');
			$power1->setIsVisible(1);
			$power1->setIsHistorized(1);
			$power1->setName(__('Puissance 1', __FILE__));
		}
		$power1->setType('info');
		$power1->setSubType('numeric');
		$power1->setEqLogic_id($this->getId());
		$power1->save();

		$voltage1 = $this->getCmd(null, 'voltage1');
		if (!is_object($voltage1)) {
			$voltage1 = new mpowerCmd();
			$voltage1->setLogicalId('voltage1');
			$voltage1->setUnite('V');
			$voltage1->setIsVisible(1);
			$voltage1->setName(__('Voltage 1', __FILE__));
		}
		$voltage1->setType('info');
		$voltage1->setSubType('numeric');
		$voltage1->setEqLogic_id($this->getId());
		$voltage1->save();

		$current1 = $this->getCmd(null, 'current1');
		if (!is_object($current1)) {
			$current1 = new mpowerCmd();
			$current1->setLogicalId('current1');
			$current1->setUnite('A');
			$current1->setIsVisible(1);
			$current1->setName(__('Courant 1', __FILE__));
		}
		$current1->setType('info');
		$current1->setSubType('numeric');
		$current1->setEqLogic_id($this->getId());
		$current1->save();

		$energy1 = $this->getCmd(null, 'energy1');
		if (!is_object($energy1)) {
			$energy1 = new mpowerCmd();
			$energy1->setLogicalId('energy1');
			$energy1->setUnite('kWh');
			$energy1->setIsVisible(1);
			$energy1->setName(__('Consommation 1', __FILE__));
		}
		$energy1->setType('info');
		$energy1->setSubType('numeric');
		$energy1->setEqLogic_id($this->getId());
		$energy1->save();

		$powerfactor1 = $this->getCmd(null, 'powerfactor1');
		if (!is_object($powerfactor1)) {
			$powerfactor1 = new mpowerCmd();
			$powerfactor1->setLogicalId('powerfactor1');
			$powerfactor1->setIsVisible(1);
			$powerfactor1->setName(__('Facteur Puissance 1', __FILE__));
		}
		$powerfactor1->setType('info');
		$powerfactor1->setSubType('numeric');
		$powerfactor1->setEqLogic_id($this->getId());
		$powerfactor1->save();

		$on1 = $this->getCmd(null, 'on1');
		if (!is_object($on1)) {
			$on1 = new mpowerCmd();
			$on1->setLogicalId('on1');
			$on1->setName(__('On 1', __FILE__));
		}
		$on1->setType('action');
		$on1->setIsVisible(0);
		$on1->setDisplay('generic_type', 'ENERGY_ON');
		$on1->setSubType('other');
		$on1->setEqLogic_id($this->getId());
		$on1->setValue($etat1id);
		$on1->save();

		$off1 = $this->getCmd(null, 'off1');
		if (!is_object($off1)) {
			$off1 = new mpowerCmd();
			$off1->setLogicalId('off1');
			$off1->setName(__('Off 1', __FILE__));
		}
		$off1->setType('action');
		$off1->setIsVisible(0);
		$off1->setDisplay('generic_type', 'ENERGY_OFF');
		$off1->setSubType('other');
		$off1->setEqLogic_id($this->getId());
		$off1->setValue($etat1id);
		$off1->save();

		if ($this->getConfiguration('model') == '3power' || $this->getConfiguration('model') == '6power') {
			$etat2 = $this->getCmd(null, 'etat2');
			if (!is_object($etat2)) {
				$etat2 = new mpowerCmd();
				$etat2->setLogicalId('etat2');
				$etat2->setName(__('Etat 2', __FILE__));
			}
			$etat2->setType('info');
			$etat2->setIsVisible(0);
			$etat2->setDisplay('generic_type', 'ENERGY_STATE');
			$etat2->setSubType('binary');
			$etat2->setEqLogic_id($this->getId());
			$etat2->save();
			$etat2id = $etat2->getId();

			$etat3 = $this->getCmd(null, 'etat3');
			if (!is_object($etat3)) {
				$etat3 = new mpowerCmd();
				$etat3->setLogicalId('etat3');
				$etat3->setName(__('Etat 3', __FILE__));
			}
			$etat3->setType('info');
			$etat3->setIsVisible(0);
			$etat3->setDisplay('generic_type', 'ENERGY_STATE');
			$etat3->setSubType('binary');
			$etat3->setEqLogic_id($this->getId());
			$etat3->save();
			$etat3id = $etat3->getId();

			$power2 = $this->getCmd(null, 'power2');
			if (!is_object($power2)) {
				$power2 = new mpowerCmd();
				$power2->setLogicalId('power2');
				$power2->setUnite('W');
				$power2->setIsHistorized(1);
				$power2->setIsVisible(1);
				$power2->setName(__('Puissance 2', __FILE__));
			}
			$power2->setType('info');
			$power2->setSubType('numeric');
			$power2->setEqLogic_id($this->getId());
			$power2->save();

			$power3 = $this->getCmd(null, 'power3');
			if (!is_object($power3)) {
				$power3 = new mpowerCmd();
				$power3->setLogicalId('power3');
				$power3->setUnite('W');
				$power3->setIsHistorized(1);
				$power3->setIsVisible(1);
				$power3->setName(__('Puissance 3', __FILE__));
			}
			$power3->setType('info');
			$power3->setSubType('numeric');
			$power3->setEqLogic_id($this->getId());
			$power3->save();

			$voltage2 = $this->getCmd(null, 'voltage2');
			if (!is_object($voltage2)) {
				$voltage2 = new mpowerCmd();
				$voltage2->setLogicalId('voltage2');
				$voltage2->setUnite('V');
				$voltage2->setIsVisible(1);
				$voltage2->setName(__('Voltage 2', __FILE__));
			}
			$voltage2->setType('info');
			$voltage2->setSubType('numeric');
			$voltage2->setEqLogic_id($this->getId());
			$voltage2->save();

			$voltage3 = $this->getCmd(null, 'voltage3');
			if (!is_object($voltage3)) {
				$voltage3 = new mpowerCmd();
				$voltage3->setLogicalId('voltage3');
				$voltage3->setUnite('V');
				$voltage3->setIsVisible(1);
				$voltage3->setName(__('Voltage 3', __FILE__));
			}
			$voltage3->setType('info');
			$voltage3->setSubType('numeric');
			$voltage3->setEqLogic_id($this->getId());
			$voltage3->save();

			$current2 = $this->getCmd(null, 'current2');
			if (!is_object($current2)) {
				$current2 = new mpowerCmd();
				$current2->setLogicalId('current2');
				$current2->setUnite('A');
				$current2->setIsVisible(1);
				$current2->setName(__('Courant 2', __FILE__));
			}
			$current2->setType('info');
			$current2->setSubType('numeric');
			$current2->setEqLogic_id($this->getId());
			$current2->save();

			$current3 = $this->getCmd(null, 'current3');
			if (!is_object($current3)) {
				$current3 = new mpowerCmd();
				$current3->setLogicalId('current3');
				$current3->setUnite('A');
				$current3->setIsVisible(1);
				$current3->setName(__('Courant 3', __FILE__));
			}
			$current3->setType('info');
			$current3->setSubType('numeric');
			$current3->setEqLogic_id($this->getId());
			$current3->save();

			$energy2 = $this->getCmd(null, 'energy2');
			if (!is_object($energy2)) {
				$energy2 = new mpowerCmd();
				$energy2->setLogicalId('energy2');
				$energy2->setUnite('kWh');
				$energy2->setIsVisible(1);
				$energy2->setName(__('Consommation 2', __FILE__));
			}
			$energy2->setType('info');
			$energy2->setSubType('numeric');
			$energy2->setEqLogic_id($this->getId());
			$energy2->save();

			$energy3 = $this->getCmd(null, 'energy3');
			if (!is_object($energy3)) {
				$energy3 = new mpowerCmd();
				$energy3->setLogicalId('energy3');
				$energy3->setUnite('kWh');
				$energy3->setIsVisible(1);
				$energy3->setName(__('Consommation 3', __FILE__));
			}
			$energy3->setType('info');
			$energy3->setSubType('numeric');
			$energy3->setEqLogic_id($this->getId());
			$energy3->save();

			$powerfactor2 = $this->getCmd(null, 'powerfactor2');
			if (!is_object($powerfactor2)) {
				$powerfactor2 = new mpowerCmd();
				$powerfactor2->setLogicalId('powerfactor2');
				$powerfactor2->setIsVisible(1);
				$powerfactor2->setName(__('Facteur Puissance 2', __FILE__));
			}
			$powerfactor2->setType('info');
			$powerfactor2->setSubType('numeric');
			$powerfactor2->setEqLogic_id($this->getId());
			$powerfactor2->save();

			$powerfactor3 = $this->getCmd(null, 'powerfactor3');
			if (!is_object($powerfactor3)) {
				$powerfactor3 = new mpowerCmd();
				$powerfactor3->setLogicalId('powerfactor3');
				$powerfactor3->setIsVisible(1);
				$powerfactor3->setName(__('Facteur Puissance 3', __FILE__));
			}
			$powerfactor3->setType('info');
			$powerfactor3->setSubType('numeric');
			$powerfactor3->setEqLogic_id($this->getId());
			$powerfactor3->save();

			$on2 = $this->getCmd(null, 'on2');
			if (!is_object($on2)) {
				$on2 = new mpowerCmd();
				$on2->setLogicalId('on2');
				$on2->setName(__('On 2', __FILE__));
			}
			$on2->setType('action');
			$on2->setIsVisible(0);
			$on2->setDisplay('generic_type', 'ENERGY_ON');
			$on2->setSubType('other');
			$on2->setEqLogic_id($this->getId());
			$on2->setValue($etat2id);
			$on2->save();

			$off2 = $this->getCmd(null, 'off2');
			if (!is_object($off2)) {
				$off2 = new mpowerCmd();
				$off2->setLogicalId('off2');
				$off2->setName(__('Off 2', __FILE__));
			}
			$off2->setType('action');
			$off2->setIsVisible(0);
			$off2->setDisplay('generic_type', 'ENERGY_OFF');
			$off2->setSubType('other');
			$off2->setEqLogic_id($this->getId());
			$off2->setValue($etat2id);
			$off2->save();

			$on3 = $this->getCmd(null, 'on3');
			if (!is_object($on3)) {
				$on3 = new mpowerCmd();
				$on3->setLogicalId('on3');
				$on3->setName(__('On 3', __FILE__));
			}
			$on3->setType('action');
			$on3->setIsVisible(0);
			$on3->setDisplay('generic_type', 'ENERGY_ON');
			$on3->setSubType('other');
			$on3->setEqLogic_id($this->getId());
			$on3->setValue($etat3id);
			$on3->save();

			$off3 = $this->getCmd(null, 'off3');
			if (!is_object($off3)) {
				$off3 = new mpowerCmd();
				$off3->setLogicalId('off3');
				$off3->setName(__('Off 3', __FILE__));
			}
			$off3->setType('action');
			$off3->setIsVisible(0);
			$off3->setDisplay('generic_type', 'ENERGY_OFF');
			$off3->setSubType('other');
			$off3->setEqLogic_id($this->getId());
			$off3->setValue($etat3id);
			$off3->save();

			$onall = $this->getCmd(null, 'onall');
			if (!is_object($onall)) {
				$onall = new mpowerCmd();
				$onall->setLogicalId('onall');
				$onall->setIsVisible(1);
				$onall->setName(__('On tous', __FILE__));
			}
			$onall->setType('action');
			$onall->setSubType('other');
			$onall->setEqLogic_id($this->getId());
			$onall->save();

			$offall = $this->getCmd(null, 'offall');
			if (!is_object($offall)) {
				$offall = new mpowerCmd();
				$offall->setLogicalId('offall');
				$offall->setIsVisible(1);
				$offall->setName(__('Off tous', __FILE__));
			}
			$offall->setType('action');
			$offall->setSubType('other');
			$offall->setEqLogic_id($this->getId());
			$offall->save();

		} else {
			$off2 = $this->getCmd(null, 'off2');
			$off3 = $this->getCmd(null, 'off3');
			$on2 = $this->getCmd(null, 'on2');
			$on3 = $this->getCmd(null, 'on3');
			$onall = $this->getCmd(null, 'onall');
			$offall = $this->getCmd(null, 'offall');
			$etat2 = $this->getCmd(null, 'etat2');
			$etat3 = $this->getCmd(null, 'etat3');
			$power2 = $this->getCmd(null, 'power2');
			$power3 = $this->getCmd(null, 'power3');
			$energy2 = $this->getCmd(null, 'energy2');
			$energy3 = $this->getCmd(null, 'energy3');
			$voltage2 = $this->getCmd(null, 'voltage2');
			$voltage3 = $this->getCmd(null, 'voltage3');
			$powerfactor2 = $this->getCmd(null, 'powerfactor2');
			$powerfactor3 = $this->getCmd(null, 'powerfactor3');
			$current2 = $this->getCmd(null, 'current2');
			$current3 = $this->getCmd(null, 'current3');

			if (is_object($off2)) {$off2->remove();}
			if (is_object($off3)) {$off3->remove();}
			if (is_object($on2)) {$on2->remove();}
			if (is_object($on3)) {$on3->remove();}
			if (is_object($onall)) {$onall->remove();}
			if (is_object($offall)) {$offall->remove();}
			if (is_object($etat2)) {$etat2->remove();}
			if (is_object($etat3)) {$etat3->remove();}
			if (is_object($power2)) {$power2->remove();}
			if (is_object($power3)) {$power3->remove();}
			if (is_object($energy2)) {$energy2->remove();}
			if (is_object($energy3)) {$energy3->remove();}
			if (is_object($voltage2)) {$voltage2->remove();}
			if (is_object($voltage3)) {$voltage3->remove();}
			if (is_object($powerfactor2)) {$powerfactor2->remove();}
			if (is_object($powerfactor3)) {$powerfactor3->remove();}
			if (is_object($current2)) {$current2->remove();}
			if (is_object($current3)) {$current3->remove();}
		}
		if ($this->getConfiguration('model') == '6power') {
			$etat4 = $this->getCmd(null, 'etat4');
			if (!is_object($etat4)) {
				$etat4 = new mpowerCmd();
				$etat4->setLogicalId('etat4');
				$etat4->setName(__('Etat 4', __FILE__));
			}
			$etat4->setType('info');
			$etat4->setIsVisible(0);
			$etat4->setDisplay('generic_type', 'ENERGY_STATE');
			$etat4->setSubType('binary');
			$etat4->setEqLogic_id($this->getId());
			$etat4->save();
			$etat4id = $etat4->getId();

			$etat5 = $this->getCmd(null, 'etat5');
			if (!is_object($etat5)) {
				$etat5 = new mpowerCmd();
				$etat5->setLogicalId('etat5');
				$etat5->setName(__('Etat 5', __FILE__));
			}
			$etat5->setType('info');
			$etat5->setIsVisible(0);
			$etat5->setDisplay('generic_type', 'ENERGY_STATE');
			$etat5->setSubType('binary');
			$etat5->setEqLogic_id($this->getId());
			$etat5->save();
			$etat5id = $etat5->getId();

			$etat6 = $this->getCmd(null, 'etat6');
			if (!is_object($etat6)) {
				$etat6 = new mpowerCmd();
				$etat6->setLogicalId('etat6');
				$etat6->setName(__('Etat 6', __FILE__));
			}
			$etat6->setType('info');
			$etat6->setIsVisible(0);
			$etat6->setDisplay('generic_type', 'ENERGY_STATE');
			$etat6->setSubType('binary');
			$etat6->setEqLogic_id($this->getId());
			$etat6->save();
			$etat6id = $etat6->getId();

			$power4 = $this->getCmd(null, 'power4');
			if (!is_object($power4)) {
				$power4 = new mpowerCmd();
				$power4->setLogicalId('power4');
				$power4->setUnite('W');
				$power4->setIsHistorized(1);
				$power4->setIsVisible(1);
				$power4->setName(__('Puissance 4', __FILE__));
			}
			$power4->setType('info');
			$power4->setSubType('numeric');
			$power4->setEqLogic_id($this->getId());
			$power4->save();

			$power5 = $this->getCmd(null, 'power5');
			if (!is_object($power5)) {
				$power5 = new mpowerCmd();
				$power5->setLogicalId('power5');
				$power5->setUnite('W');
				$power5->setIsHistorized(1);
				$power5->setIsVisible(1);
				$power5->setName(__('Puissance 5', __FILE__));
			}
			$power5->setType('info');
			$power5->setSubType('numeric');
			$power5->setEqLogic_id($this->getId());
			$power5->save();

			$power6 = $this->getCmd(null, 'power6');
			if (!is_object($power6)) {
				$power6 = new mpowerCmd();
				$power6->setLogicalId('power6');
				$power6->setUnite('W');
				$power6->setIsHistorized(1);
				$power6->setIsVisible(1);
				$power6->setName(__('Puissance 6', __FILE__));
			}
			$power6->setType('info');
			$power6->setSubType('numeric');
			$power6->setEqLogic_id($this->getId());
			$power6->save();

			$voltage4 = $this->getCmd(null, 'voltage4');
			if (!is_object($voltage4)) {
				$voltage4 = new mpowerCmd();
				$voltage4->setLogicalId('voltage4');
				$voltage4->setUnite('V');
				$voltage4->setIsVisible(1);
				$voltage4->setName(__('Voltage 4', __FILE__));
			}
			$voltage4->setType('info');
			$voltage4->setSubType('numeric');
			$voltage4->setEqLogic_id($this->getId());
			$voltage4->save();

			$voltage5 = $this->getCmd(null, 'voltage5');
			if (!is_object($voltage5)) {
				$voltage5 = new mpowerCmd();
				$voltage5->setLogicalId('voltage5');
				$voltage5->setUnite('V');
				$voltage5->setIsVisible(1);
				$voltage5->setName(__('Voltage 5', __FILE__));
			}
			$voltage5->setType('info');
			$voltage5->setSubType('numeric');
			$voltage5->setEqLogic_id($this->getId());
			$voltage5->save();

			$voltage6 = $this->getCmd(null, 'voltage6');
			if (!is_object($voltage6)) {
				$voltage6 = new mpowerCmd();
				$voltage6->setLogicalId('voltage6');
				$voltage6->setUnite('V');
				$voltage6->setIsVisible(1);
				$voltage6->setName(__('Voltage 6', __FILE__));
			}
			$voltage6->setType('info');
			$voltage6->setSubType('numeric');
			$voltage6->setEqLogic_id($this->getId());
			$voltage6->save();

			$current4 = $this->getCmd(null, 'current4');
			if (!is_object($current4)) {
				$current4 = new mpowerCmd();
				$current4->setLogicalId('current4');
				$current4->setUnite('A');
				$current4->setIsVisible(1);
				$current4->setName(__('Courant 4', __FILE__));
			}
			$current4->setType('info');
			$current4->setSubType('numeric');
			$current4->setEqLogic_id($this->getId());
			$current4->save();

			$current5 = $this->getCmd(null, 'current5');
			if (!is_object($current5)) {
				$current5 = new mpowerCmd();
				$current5->setLogicalId('current5');
				$current5->setUnite('A');
				$current5->setIsVisible(1);
				$current5->setName(__('Courant 5', __FILE__));
			}
			$current5->setType('info');
			$current5->setSubType('numeric');
			$current5->setEqLogic_id($this->getId());
			$current5->save();

			$current6 = $this->getCmd(null, 'current6');
			if (!is_object($current6)) {
				$current6 = new mpowerCmd();
				$current6->setLogicalId('current6');
				$current6->setUnite('A');
				$current6->setIsVisible(1);
				$current6->setName(__('Courant 6', __FILE__));
			}
			$current6->setType('info');
			$current6->setSubType('numeric');
			$current6->setEqLogic_id($this->getId());
			$current6->save();

			$energy4 = $this->getCmd(null, 'energy4');
			if (!is_object($energy4)) {
				$energy4 = new mpowerCmd();
				$energy4->setLogicalId('energy4');
				$energy4->setUnite('kWh');
				$energy4->setIsVisible(1);
				$energy4->setName(__('Consommation 4', __FILE__));
			}
			$energy4->setType('info');
			$energy4->setSubType('numeric');
			$energy4->setEqLogic_id($this->getId());
			$energy4->save();

			$energy5 = $this->getCmd(null, 'energy5');
			if (!is_object($energy5)) {
				$energy5 = new mpowerCmd();
				$energy5->setLogicalId('energy5');
				$energy5->setUnite('kWh');
				$energy5->setIsVisible(1);
				$energy5->setName(__('Consommation 5', __FILE__));
			}
			$energy5->setType('info');
			$energy5->setSubType('numeric');
			$energy5->setEqLogic_id($this->getId());
			$energy5->save();

			$energy6 = $this->getCmd(null, 'energy6');
			if (!is_object($energy6)) {
				$energy6 = new mpowerCmd();
				$energy6->setLogicalId('energy6');
				$energy6->setUnite('kWh');
				$energy6->setIsVisible(1);
				$energy6->setName(__('Consommation 6', __FILE__));
			}
			$energy6->setType('info');
			$energy6->setSubType('numeric');
			$energy6->setEqLogic_id($this->getId());
			$energy6->save();

			$powerfactor4 = $this->getCmd(null, 'powerfactor4');
			if (!is_object($powerfactor4)) {
				$powerfactor4 = new mpowerCmd();
				$powerfactor4->setLogicalId('powerfactor4');
				$powerfactor4->setIsVisible(1);
				$powerfactor4->setName(__('Facteur Puissance 4', __FILE__));
			}
			$powerfactor4->setType('info');
			$powerfactor4->setSubType('numeric');
			$powerfactor4->setEqLogic_id($this->getId());
			$powerfactor4->save();

			$powerfactor5 = $this->getCmd(null, 'powerfactor5');
			if (!is_object($powerfactor5)) {
				$powerfactor5 = new mpowerCmd();
				$powerfactor5->setLogicalId('powerfactor5');
				$powerfactor5->setIsVisible(1);
				$powerfactor5->setName(__('Facteur Puissance 5', __FILE__));
			}
			$powerfactor5->setType('info');
			$powerfactor5->setSubType('numeric');
			$powerfactor5->setEqLogic_id($this->getId());
			$powerfactor5->save();

			$powerfactor6 = $this->getCmd(null, 'powerfactor6');
			if (!is_object($powerfactor6)) {
				$powerfactor6 = new mpowerCmd();
				$powerfactor6->setLogicalId('powerfactor6');
				$powerfactor6->setIsVisible(1);
				$powerfactor6->setName(__('Facteur Puissance 6', __FILE__));
			}
			$powerfactor6->setType('info');
			$powerfactor6->setSubType('numeric');
			$powerfactor6->setEqLogic_id($this->getId());
			$powerfactor6->save();

			$on4 = $this->getCmd(null, 'on4');
			if (!is_object($on4)) {
				$on4 = new mpowerCmd();
				$on4->setLogicalId('on4');
				$on4->setName(__('On 4', __FILE__));
			}
			$on4->setType('action');
			$on4->setIsVisible(0);
			$on4->setDisplay('generic_type', 'ENERGY_ON');
			$on4->setSubType('other');
			$on4->setEqLogic_id($this->getId());
			$on4->setValue($etat4id);
			$on4->save();

			$off4 = $this->getCmd(null, 'off4');
			if (!is_object($off4)) {
				$off4 = new mpowerCmd();
				$off4->setLogicalId('off4');
				$off4->setName(__('Off 4', __FILE__));
			}
			$off4->setType('action');
			$off4->setIsVisible(0);
			$off4->setDisplay('generic_type', 'ENERGY_OFF');
			$off4->setSubType('other');
			$off4->setEqLogic_id($this->getId());
			$off4->setValue($etat4id);
			$off4->save();

			$on5 = $this->getCmd(null, 'on5');
			if (!is_object($on5)) {
				$on5 = new mpowerCmd();
				$on5->setLogicalId('on5');
				$on5->setName(__('On 5', __FILE__));
			}
			$on5->setType('action');
			$on5->setIsVisible(0);
			$on5->setDisplay('generic_type', 'ENERGY_ON');
			$on5->setSubType('other');
			$on5->setEqLogic_id($this->getId());
			$on5->setValue($etat5id);
			$on5->save();

			$off5 = $this->getCmd(null, 'off5');
			if (!is_object($off5)) {
				$off5 = new mpowerCmd();
				$off5->setLogicalId('off5');
				$off5->setName(__('Off 5', __FILE__));
			}
			$off5->setType('action');
			$off5->setIsVisible(0);
			$off5->setDisplay('generic_type', 'ENERGY_OFF');
			$off5->setSubType('other');
			$off5->setEqLogic_id($this->getId());
			$off5->setValue($etat5id);
			$off5->save();

			$on6 = $this->getCmd(null, 'on6');
			if (!is_object($on6)) {
				$on6 = new mpowerCmd();
				$on6->setLogicalId('on6');
				$on6->setName(__('On 6', __FILE__));
			}
			$on6->setType('action');
			$on6->setIsVisible(0);
			$on6->setDisplay('generic_type', 'ENERGY_ON');
			$on6->setSubType('other');
			$on6->setEqLogic_id($this->getId());
			$on6->setValue($etat6id);
			$on6->save();

			$off6 = $this->getCmd(null, 'off6');
			if (!is_object($off6)) {
				$off6 = new mpowerCmd();
				$off6->setLogicalId('off6');
				$off6->setName(__('Off 6', __FILE__));
			}
			$off6->setType('action');
			$off6->setIsVisible(0);
			$off6->setDisplay('generic_type', 'ENERGY_OFF');
			$off6->setSubType('other');
			$off6->setEqLogic_id($this->getId());
			$off6->setValue($etat6id);
			$off6->save();
		} else {
			$off4 = $this->getCmd(null, 'off4');
			$off5 = $this->getCmd(null, 'off5');
			$off6 = $this->getCmd(null, 'off6');
			$on4 = $this->getCmd(null, 'on4');
			$on5 = $this->getCmd(null, 'on5');
			$on6 = $this->getCmd(null, 'on6');
			$etat4 = $this->getCmd(null, 'etat4');
			$etat5 = $this->getCmd(null, 'etat5');
			$etat6 = $this->getCmd(null, 'etat6');
			$power4 = $this->getCmd(null, 'power4');
			$power5 = $this->getCmd(null, 'power5');
			$power6 = $this->getCmd(null, 'power6');
			$energy4 = $this->getCmd(null, 'energy4');
			$energy5 = $this->getCmd(null, 'energy5');
			$energy6 = $this->getCmd(null, 'energy6');
			$voltage4 = $this->getCmd(null, 'voltage4');
			$voltage5 = $this->getCmd(null, 'voltage5');
			$voltage6 = $this->getCmd(null, 'voltage6');
			$powerfactor4 = $this->getCmd(null, 'powerfactor4');
			$powerfactor5 = $this->getCmd(null, 'powerfactor5');
			$powerfactor6 = $this->getCmd(null, 'powerfactor6');
			$current4 = $this->getCmd(null, 'current4');
			$current5 = $this->getCmd(null, 'current5');
			$current6 = $this->getCmd(null, 'current6');

			if (is_object($off4)) {$off4->remove();}
			if (is_object($off5)) {$off5->remove();}
			if (is_object($off6)) {$off6->remove();}
			if (is_object($on4)) {$on4->remove();}
			if (is_object($on5)) {$on5->remove();}
			if (is_object($on6)) {$on6->remove();}
			if (is_object($etat4)) {$etat4->remove();}
			if (is_object($etat5)) {$etat5->remove();}
			if (is_object($etat6)) {$etat6->remove();}
			if (is_object($power4)) {$power4->remove();}
			if (is_object($power5)) {$power5->remove();}
			if (is_object($power6)) {$power6->remove();}
			if (is_object($energy4)) {$energy4->remove();}
			if (is_object($energy5)) {$energy5->remove();}
			if (is_object($energy6)) {$energy6->remove();}
			if (is_object($voltage4)) {$voltage4->remove();}
			if (is_object($voltage5)) {$voltage5->remove();}
			if (is_object($voltage6)) {$voltage6->remove();}
			if (is_object($powerfactor4)) {$powerfactor4->remove();}
			if (is_object($powerfactor5)) {$powerfactor5->remove();}
			if (is_object($powerfactor6)) {$powerfactor6->remove();}
			if (is_object($current4)) {$current4->remove();}
			if (is_object($current5)) {$current5->remove();}
			if (is_object($current6)) {$current6->remove();}
		}

		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new mpowerCmd();
			$refresh->setLogicalId('refresh');
			$refresh->setIsVisible(1);
			$refresh->setName(__('Rafraichir', __FILE__));
		}
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->setEqLogic_id($this->getId());
		$refresh->save();

	}

	public function postAjax() {
		$this->cron($this->getId());
	}

	public function toHtml($_version = 'dashboard') {
		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}
		$version = jeedom::versionAlias($_version);
		if ($this->getDisplay('hideOn' . $version) == 1) {
			return '';
		}
		foreach ($this->getCmd() as $cmd) {
			if ($cmd->getType() == 'info') {
				$replace['#' . $cmd->getLogicalId() . '_history#'] = '';
				$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
				$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
				$replace['#' . $cmd->getLogicalId() . '_collectDate#'] = $cmd->getCollectDate();
				if ($cmd->getIsHistorized() == 1) {
					$replace['#' . $cmd->getLogicalId() . '_history#'] = 'history cursor';
				}
			} else {
				$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			}
		}
		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, strtolower($this->getConfiguration('model')), 'mpower')));
	}
}

class mpowerCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function execute($_options = null) {
		if ($this->getType() == '') {
			return '';
		}
		$action = $this->getLogicalId();
		$eqLogic = $this->getEqlogic();
		if ($action == 'refresh') {
			$eqLogic->cron($eqLogic->getId());
		} else {
			$i = 0;
			$sessid = mt_rand(1, 9);
			do {
				$sessid .= mt_rand(0, 9);
			} while (++$i < 31);
			$ipmpower = $eqLogic->getConfiguration('addr');
			$user = $eqLogic->getConfiguration('user');
			$pwd = $eqLogic->getConfiguration('pwd');
			$cmd = 'curl -X POST -d "username=' . $user . '&password=' . $pwd . '" -b "AIROS_SESSIONID=' . $sessid . '" ' . $ipmpower . '/login.cgi';
			exec($cmd);
			log::add('mpower', 'debug', $cmd);
			if (strpos($action, 'on') !== false) {
				$output = '1';
			} elseif (strpos($action, 'off') !== false) {
				$output = '0';
			}
			$sensor = substr($action, -1);
			if ($sensor == 'l') {
				$i = 1;
				$sensornumber = substr($eqLogic->getConfiguration('model'), 0, 1);
				do {
					$cmd = 'curl -X PUT -d "output=' . $output . '" -b "AIROS_SESSIONID=' . $sessid . '" ' . $ipmpower . '/sensors/' . $i;
					exec($cmd);
					log::add('mpower', 'debug', $cmd);
				} while (++$i <= $sensornumber);
			} else {
				$cmd = 'curl -X PUT -d "output=' . $output . '" -b "AIROS_SESSIONID=' . $sessid . '" ' . $ipmpower . '/sensors/' . $sensor;
				exec($cmd);
				log::add('mpower', 'debug', $cmd);
			}
			sleep(1);
			$mpowerinfo = $eqLogic->getmpowerInfo($sessid);
			$cmd = 'curl -b "AIROS_SESSIONID=' . $sessid . '" ' . $ipmpower . '/logout.cgi';
			exec($cmd);
			log::add('mpower', 'debug', $cmd);
		}
	}

	/*     * **********************Getteur Setteur*************************** */
}
?>
