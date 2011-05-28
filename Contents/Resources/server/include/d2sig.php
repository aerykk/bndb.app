<?php

require_once("database.php");
require_once("file.php");

class d2sig
{
	public static $before = array("attributes", "bow and crossbow", "paladin", " (pally only)", "faster run/walk", "enhanced defense", "enhanced damage", " (amazon only)", " (necromancer only)", " over ", "gauntlets", "seconds", "poison", "resist", "fire res", "cold res", "lightning res", "psn res", " (sorceress only)", " (barbarian only)", "better Chance of Getting Magic Items", "two-hand", "poison and bone", "mana stolen per hit", "life stolen per hit", "maximum", "minimum", "crushing blow", "skill levels", "sorceress", "faster hit recovery", "faster cast rate", "mace class - very fast attack speed", "can be inserted into socketed items", " to ", "Damage Reduced by", "Defense", "Magic Find", "Level", "Attack Rating", "Keep in Inventory Gain Bonus", "to Life", "to Mana", "Javelin and Spear Skills (Amazon Only)", "Warcries (Barbarian Only)", "poison damage over", "Dexterity", "Strength", "Required", "Durability", " of ", "Adds ", "lightning", "damage", "One-Hand");
	public static $after = array("attr", "bow", "pally", '', "frw", "ed", "ed", '', '', " / ", "gaunts", "sec", "psn", "res", "fr", "cr", "lr", "pr", '', '', "mf", "2h", "pnb", "ml", "ll", "max", "min", "cb", "skills", "sorc", "fhr", "fcr", '', '', ' ', "reduce dmg", "def", "mf", "lvl", "ar", '', "life", "mana", "javs", "warcries", "/", "dex", "str", "req", "dur", "/", "+", "lite", "dmg", "1h");

	public function __construct()
	{

	}

	private function get_color_from_code($im, $code)
	{
		if($code == "c3") // magic
			return ImageColorAllocate($im, 179, 226, 255);
		else if($code == "c4") // unique
			return ImageColorAllocate($im, 255, 225, 143);
		else if($code == "c9") // rare
			return ImageColorAllocate($im, 255, 255, 179);
		else if($code == "c8") // rune
			return ImageColorAllocate($im, 255, 255, 179);
		else if($code == "c5") // white
			return ImageColorAllocate($im, 255, 255, 255);
		else if($code == "c2") // set
			return ImageColorAllocate($im, 68, 224, 64);
		else if($code == "c0") // gem
			return ImageColorAllocate($im, 255, 255, 255);
		else
			return ImageColorAllocate($im, 3, 56, 126);
	}

	public function show()
	{
		$self = &$this;

		header("Content-type: image/png");

		$filename = "cache.txt";

		if(file_exists($filename))
		{
			$mtime = filemtime($filename);
			
			$updated_since = time() - $mtime;
			
			if($updated_since < 3 * 60)
				die(file_get_contents($filename));
		}

		$im = imagecreatetruecolor(400, 150);//440.771, 165.453);
		imagecolortransparent($im, imagecolorallocate($im, 0, 0, 0));

		$title = "DAEMN'S UPDATED FT LIST...";
		$title_color = ImageColorAllocate($im, 255, 255, 255);
		$title_shadow_color = ImageColorAllocate($im, 3, 56, 126);
		imagestring($im, 3, 5 - 1, 5, $title, $title_shadow_color);
		imagestring($im, 3, 5, 5 - 1, $title, $title_shadow_color);
		imagestring($im, 3, 5 + 1, 5, $title, $title_shadow_color);
		imagestring($im, 3, 5, 5 + 1, $title, $title_shadow_color);
		imagestring($im, 3, 5 + 1, 5 + 1, $title, $title_shadow_color);
		imagestring($im, 3, 5 - 1, 5 - 1, $title, $title_shadow_color);
		imagestring($im, 3, 5, 5, $title, $title_color);

		$x1 = preg_replace('/[^(\x20-\x7F)\x0A\x0D]*/','', file_get_contents("log.txt"));

		$items = array_slice(explode("\n", $x1), 0, 9);

		foreach($items as $i => $item)
		{
			$item = json_decode($item, true);

			if(!$item)
				continue;

			$time = $item['timestamp'];
			$details_str = $item['details'];

			$details = explode("|", $details_str);

			if(!preg_match("/#(..)([^#]+)(#..)?$/simU", $details[0], $x2))
				continue;

			$title = trim($x2[2]);
			$title_shadow_color = ImageColorAllocate($im, 3, 56, 126);//165, 169, 184);
			$title_color = $this->get_color_from_code($im, $x2[1]);

			imagestring($im, 3, 13 - 1, 22 + (12 * $i), $title, $title_shadow_color);
			imagestring($im, 3, 13, 22 + (12 * $i) - 1, $title, $title_shadow_color);
			imagestring($im, 3, 13 + 1, 22 + (12 * $i), $title, $title_shadow_color);
			imagestring($im, 3, 13, 22 + (12 * $i) + 1, $title, $title_shadow_color);
			imagestring($im, 3, 13 + 1, 22 + (12 * $i) + 1, $title, $title_shadow_color);
			imagestring($im, 3, 13 - 1, 22 + (12 * $i) - 1, $title, $title_shadow_color);

			imagestring($im, 3, 13, 22 + (12 * $i), $title, $title_color);

			$title_color = $this->get_color_from_code($im, NULL);

			$x = strlen($title) * imagefontwidth(3) + 20;

			if(stristr($details_str, "unidentified"))
			{
				imagestring($im, 3, $x, 22 + (12 * $i), "unid", $title_color);
			}
			else
			{
				$text = '';

				foreach(array_slice($details, 1) as $detail)
				{
					$detail = preg_replace("/#(..)/simU", '', $detail);

					$detail = str_ireplace(self::$before, self::$after, strtolower($detail));

					if($detail && !stristr($detail, "dur:"))
						$text .= $detail . "; ";
				}

				imagestring($im, 1, $x, 25 + (12 * $i), $text, $title_color);
			}
		}

		$title = "...VISIT MY PROFILE FOR TOPIC AND DETAILS";
		$title_color = ImageColorAllocate($im, 255, 255, 255);
		$title_shadow_color = ImageColorAllocate($im, 3, 56, 126);
		imagestring($im, 3, 110 - 1, 135, $title, $title_shadow_color);
		imagestring($im, 3, 110, 135 - 1, $title, $title_shadow_color);
		imagestring($im, 3, 110 + 1, 135, $title, $title_shadow_color);
		imagestring($im, 3, 110, 135 + 1, $title, $title_shadow_color);
		imagestring($im, 3, 110 + 1, 135 + 1, $title, $title_shadow_color);
		imagestring($im, 3, 110 - 1, 135 - 1, $title, $title_shadow_color);
		imagestring($im, 3, 110, 135, $title, $title_color);

		ob_start();
		imagegif($im);
		$data = ob_get_contents();
		ob_end_clean();

		$f1 = new file("cache.txt", "w");

		$f1->write_all($data);

		imagedestroy($im);

		die($data);
	}
}

?>