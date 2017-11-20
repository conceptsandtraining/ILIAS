<?php

/* Copyright (c) 1998-2014 ILIAS open source, Extended GPL, see docs/LICENSE */#

/**
* Utilities for generali users.
*
* @author	Richard Klees <richard.klees@concepts-and-training.de>
* @version	$Id$
*/

require_once("Services/Calendar/classes/class.ilDate.php");
require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");
require_once("Services/GEV/Utils/classes/class.gevUserUtils.php");


class gevBillingUtils {
	const BILL_VAT = 19;
	const BILL_CURRENCY = "EUR";
	
	static protected $instance = null;

	protected function __construct() {
		global $lng, $ilLog, $ilDB;
		$this->lng = &$lng;
		$this->log = &$ilLog;
		$this->db = &$ilDB;
		
		$this->lng->loadLanguageModule("gev");
	}
	
	static public function getInstance() {
		if (self::$instance === null) {
			self::$instance = new gevBillingUtils();
		}
		
		return self::$instance;
	}
	
	static public function formatPrize($a_prize) {
		return number_format((float)$a_prize, 2, ",", ".");
	}
	
	public function isValidCouponCode($a_code) {
		require_once("Services/Billing/classes/class.ilCoupons.php");
		return ilCoupons::getSingleton()->isValidCode($a_code);
	}
	
	public function getCouponValues($a_coupons) {
		require_once("Services/Billing/classes/class.ilCoupon.php");
		$coupon_dummy = new ilCoupon();
		$ret = array();
		foreach($a_coupons as $coupon) {
			$coup = $coupon_dummy->getInstance($coupon);
			$ret[$coupon] = $coup->getValue();
		}
		return $ret;
	}
	
	public function getPrizeIncludingCoupons($a_prize, $a_coupons) {
		require_once("Services/Billing/classes/class.ilCoupon.php");
		$a_prize = (float)$a_prize;
		$coupon_dummy = new ilCoupon();
		
		// Take Coupon codes as long as the prize is
		// larger then 0.
		foreach($a_coupons as $code) {
			$coupon = $coupon_dummy->getInstance($code);
			if ($coupon->isExpired()) {
				continue;
			}
			$value = $coupon->getValue();
			if ($a_prize > $value) {
				// Take complete coupon value and preceed afterwards
				$diff = $value;
				$break = false;
			}
			else {
				// Take only the leftover of the fee.
				$diff = $a_prize;
				$break = true;
			}
			$a_prize -= $diff;
			
			if ($break) {
				break;
			}
		}
		
		return $a_prize;
	}
	
	public function createCourseBill( $a_user_id
									, $a_crs_id
									, $a_recipient
									, $a_agency
									, $a_street
									, $a_housenumber
									, $a_zipcode
									, $a_city
									, $a_costcenter
									, $a_coupons
									, $a_email
									) {
		require_once("Services/Billing/classes/class.ilBill.php");
		require_once("Services/Billing/classes/class.ilCoupon.php");
		
		// #758: cancel all previous bills
		$this->cancelBill($a_crs_id, $a_user_id);
		
		$user_utils = gevUserUtils::getInstance($a_user_id);
		$crs_utils = gevCourseUtils::getInstance($a_crs_id);
		
		$bill = new ilBill();
		$bill->setBillyear(date("Y"));
		$bill->setContextId($a_crs_id);
		$bill->setRecipientName($a_agency.", ".$a_recipient);
		$bill->setRecipientStreet($a_street);
		$bill->setRecipientHousenumber($a_housenumber);
		$bill->setRecipientZipcode($a_zipcode);
		$bill->setRecipientCity($a_city);
		$bill->setRecipientCountry("");
		$bill->setRecipientEmail($a_email);
		$bill->setDate(new ilDate(time(), IL_CAL_UNIX));
		$bill->setTitle(sprintf( $this->lng->txt("gev_course_bill_title")
							   , $crs_utils->getTitle()
							   , $user_utils->getFirstname()." ".$user_utils->getLastname()
							   )
						);
		$bill->setVAT(self::BILL_VAT);
		$bill->setCostCenter($a_costcenter);
		$bill->setCurrency(self::BILL_CURRENCY);
		$bill->setUserId($a_user_id);
		$bill->create();

		$fee = $crs_utils->getFee();
		
		$this->createItem( sprintf($this->lng->txt("gev_fee_bill_item")
								  , $a_crs_id
								  )
						 , $fee
						 , $a_crs_id
						 , $bill
						);
		
		$coupon_dummy = new ilCoupon();
		
		foreach($a_coupons as $code) {
			$this->chargeCouponAgainstBill($code, $bill);
		}
		
		$bill->update();
		
		$this->log->write("gevBillingUtils::createCourseBill: created bill with id '".$bill->getId()."'".
						  " for user ".$a_user_id." at course ".$a_crs_id);
	}

	public function chargeCouponAgainstBill($a_coupon_code, ilBill $a_bill) {
		require_once("Services/Billing/classes/class.ilCoupon.php");
		$amount_left = $a_bill->getAmount();

		if($amount_left == 0) {
			ilUtil::sendInfo($this->lng->txt("gev_bill_covered_skip_coupons"));
			return false;
		}
		$coupon_dummy = new ilCoupon();
		try {
			$coupon = $coupon_dummy->getInstance($a_coupon_code);
		} catch (ilException $e) {
			ilUtil::sendFailure($e->getMessage());
			return false;
		}
		if ($coupon->isExpired()) {
			return false;
		}
		$value = $coupon->getValue();
		if($value == 0) {
			ilUtil::sendInfo($this->lng->txt("gev_cupon_has_no_value"));
			return false;
		}
		if ($amount_left > $value) {
			$diff = $value;
		}
		else {
			$diff = $amount_left;
		}
		$coupon->subtractValue($diff);
		$this->createItem( sprintf($this->lng->txt("gev_coupon_bill_item")
								  , $a_coupon_code
								  )
						 , -1 * $diff
						 , null
						 , $a_bill
						 );

		return true;
	}

	/**
	* get all coupon codes, that belong to a bill
	* @param ilBill $a_bill
	* @return array
	*/
	public function getCouponCodesAssociatedWithBill(ilBill $a_bill) {
		$bill_items = $a_bill->getItems();
		$codes = array();
		foreach($bill_items as $bill_item) {
			$bill_item_amount = $bill_item->getAmount();
			if( $bill_item_amount >= 0 ) {
				continue;
			}
			$bill_item_title = $bill_item->getTitle();
			$bill_item_title = explode(" ", $bill_item_title);
			$code = implode("",array_slice($bill_item_title,1));
			
			$codes[] = $code;
		}
		return $codes;
	}

	protected function createItem( $a_title
								 , $a_posttax_amount
								 , $a_context_id
								 , ilBill $bill
								 ) {
		$item = new ilBillItem();
		$item->setTitle($a_title);
		$item->setPreTaxAmount($a_posttax_amount/(1.0 + self::BILL_VAT/100.0));
		$item->setVAT(self::BILL_VAT);
		$item->setCurrency(self::BILL_CURRENCY);
		$item->setContextId($a_context_id);
		$item->setBill($bill);
		$item->create();
		$bill->addItem($item);
		return $item;
	}
	
	protected function resetCouponValuesFromItems($a_items) {
		require_once("Services/Billing/classes/class.ilCoupon.php");
		$coupon_dummy = new ilCoupon();
		foreach ($a_items as $item) {
			$spl = explode(" ", $item->getTitle());
			// items of coupons have a title starting with 'Gutschein'
			// (see createCourseBill) and do not have a context id.
			if ($spl[0] != "Gutschein" || $item->getContextId()) {
				continue;
			}
			$code = $spl[1];
			// if the item is finalized already, something went terribly wrong
			if ($item->isFinalized()) {
				$this->log->write("gevBillingUtils::createCourseBill: ".
								  "item '".$item->getId()." for coupon '".$code."' ".
								  "already finalized.");
				continue;
			}
			try {
				$coupon = $coupon_dummy->getInstance($code);
			}
			catch (ilException $e) {
				$this->log->write("gevBillingUtils::createCourseBill: ".
								  "Tried to reset value for coupon with code '".$code."'".
								  " but could not get coupon instance.");
				continue;
			}
			$coupon->addValue(-1 * $item->getAmount());
		}
	}
	
	public function getBillsForCourseAndUser($a_user_id, $a_crs_id) {
		require_once("Services/Billing/classes/class.ilBill.php");
		return ilBill::getInstancesByUserAndContext($a_user_id, $a_crs_id);
	}
	
	public function getNonFinalizedBillForCourseAndUser($a_crs_id, $a_user_id) {
		$bills = $this->getBillsForCourseAndUser($a_user_id, $a_crs_id);
		
		// discard finalized bills
		foreach ($bills as $key => $bill) {
			if ($bill->isFinalized()) {
				unset($bills[$key]);
			}
		}
		
		$amount_bills = count($bills);
		
		if ($amount_bills == 0) {
			// there is no bill for the user at the course, so we don't need 
			// to do anything.
			return null;
		}
		
		if ($amount_bills > 1) {
			// this is an assumption about the booking process. There should
			// never be more than one bill per course and user.
			$this->log->write("gevBillingUtils::getNonFinalizedBillForCourseAndUser: ".
						  "There is more than one non finalized bill for user ".$a_user_id.
						  " at course ".$a_crs_id.", this violates a crucial ".
						  "assumption about the booking process."
						  );
			return null;
		}
		
		return $bills[0];
	}
	
	public function finalizeBill($a_crs_id, $a_user_id) {
		$bill = $this->getNonFinalizedBillForCourseAndUser($a_crs_id, $a_user_id);
		if ($bill === null) {
			return;
		}
		
		$crs_utils = gevCourseUtils::getInstance($a_crs_id);
		$date = $crs_utils->getEndDate();
		
		$bill->setDate($date ? $date : (new ilDate(time(), IL_CAL_UNIX)));
		$bill->update();
		
		$bill->finalize();
	}
	
	public function finalizeNoShowBill($a_crs_id, $a_user_id) {
		$bill = $this->getNonFinalizedBillForCourseAndUser($a_crs_id, $a_user_id);
		if ($bill === null) {
			return;
		}
		
		$crs_utils = gevCourseUtils::getInstance($a_crs_id);
		$user_utils = gevUserUtils::getInstance($a_user_id);
		
		$bill->setTitle(sprintf( $this->lng->txt("gev_no_show_bill_title")
							   , $crs_utils->getTitle()
							   , $user_utils->getFirstname()." ".$user_utils->getLastname()
							   )
						);

		$date = $crs_utils->getEndDate();
		$bill->setDate($date ? $date : (new ilDate(time(), IL_CAL_UNIX)));

		// search for the item regarding the course...
		$items = $bill->getItems();
		foreach ($items as $item) {
			if ($item->getContextId() == $a_crs_id) {
				// ... and change its title appropriately
				$item->setTitle(sprintf( $this->lng->txt("gev_no_show_bill_item")
									   , $a_crs_id
									   )
								);
				$item->update();
			}
		}
		
		$bill->finalize();
	}
	
	public function cancelBill($a_crs_id, $a_user_id) {
		$bill = $this->getNonFinalizedBillForCourseAndUser($a_crs_id, $a_user_id);
		if ($bill === null) {
			return;
		}
	
		$items = $bill->getItems();
		$this->resetCouponValuesFromItems($items);
		foreach ($items as $item) {
			if ($item->isFinalized()) {
				$this->log->write("gevBillingUtils::cancelBill: item '".$item->getId()."' ".
								  "in bill '".$bill->getId()."' already finalized. This should ".
								  "not happen...");
			}
			else {
				$item->delete();
			}
		}
		$bill->delete();
		$this->log->write("gevBillingUtils::cancelBill: deleted bill ".$bill->getId()." for user ".$a_user_id.
						  " at course ".$a_crs_id.".");
	}
	
	public function createAllCancellationBillsAndCoupons($a_crs_id) {
		require_once("Services/GEV/Utils/classes/class.gevCourseUtils.php");
		
		$crs_utils = gevCourseUtils::getInstance($a_crs_id);
		$cancelled = $crs_utils->getBookings()->getCancelledWithCostsUsers();
		
		global $ilLog;
		
		foreach($cancelled as $usr_id) {
			$ilLog->write("gevBillingUtils::createAllCancellationBillsAndCoupons: create cancellation bill(s) ".
						  "for user ".$usr_id." at course ".$a_crs_id);
			$this->createCancellationBillAndCoupon($a_crs_id, $usr_id);
		}
	}
	
	public function createCancellationBillAndCoupon($a_crs_id, $a_user_id) {
		global $ilLog;

		$bill = $this->getNonFinalizedBillForCourseAndUser($a_crs_id, $a_user_id);
		if ($bill === null) {
			$ilLog->write("gevBillingUtils::createCancellationBillAndCoupon no bill for user/course crs=" .$a_crs_id . " user=" .$a_user_id);
			return;
		}
		
		require_once("Services/Billing/classes/class.ilCoupons.php");
		
		$crs_utils = gevCourseUtils::getInstance($a_crs_id);
		$user_utils = gevUserUtils::getInstance($a_user_id);
		
		$bill->setTitle(sprintf( $this->lng->txt("gev_cancellation_bill_title")
							   , $crs_utils->getTitle()
							   , $user_utils->getFirstname()." ".$user_utils->getLastname()
							   )
						);
		
		$date = $crs_utils->getEndDate();
		$bill->setDate($date ? $date : (new ilDate(time(), IL_CAL_UNIX)));
		
		// remove context to make assumption one bill per course and user
		// be correct.
		$bill->setContextId($a_crs_id);

		// search for the item regarding the course...
		$items = $bill->getItems();
		foreach ($items as $item) {
			if ($item->getContextId() == $a_crs_id) {
				// ... and change its title appropriately
				$item->setTitle(sprintf( $this->lng->txt("gev_cancellation_bill_item")
									   , $a_crs_id
									   )
								);
				$item->update();
			}
		}
		$bill->update();

		$coupon_code = ilCoupons::getSingleton()->createCoupon((float)$crs_utils->getFee(), time() + 365 * 24 * 60 * 60, "", $a_user_id);
		
		$this->db->manipulate("INSERT INTO gev_bill_coupon (bill_pk,coupon_code) VALUES "
							  ."(".$this->db->quote($bill->getId(), "integer").", ".$this->db->quote($coupon_code, "text").")");

		$bill->finalize();

		$this->log->write("gevBillingUtils::createCancellationBillAndCoupon: created cancelation bill '"
						 .$bill->getid()."' for course '".$a_crs_id."' and user '".$a_user_id."' with "
						 ."coupon '".$coupon_code."'");
	}
}

?>