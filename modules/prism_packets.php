<?php
/**
 * PHPInSimMod - Packet Module
 * @package PRISM
 * @subpackage Packet
 */

/* Start of PRISM PACKET HEADER */
abstract class struct
{
	public function __construct($rawPacket = NULL)
	{
		if ($rawPacket !== NULL)
		{
			$this->unpack($rawPacket);
		}
	}
	public function __toString()
	{
		return $this->pack();
	}
	public function unpack($rawPacket)
	{
		global $TYPEs;
		$unPackFormat = $this::parsePackFormat();
		$propertyNumber = -1;
		$pkClass = unpack($this::UNPACK, $rawPacket);
		echo $TYPEs[$this->Type] . ' Object {' . PHP_EOL;
		foreach ($this as $property => $value)
		{
			$pkFnkFormat = $unPackFormat[++$propertyNumber];
			$this->$property = $pkClass[$property];
			echo "\t{$pkFnkFormat}\t{$property}\t= " . var_export($this->$property, TRUE) . PHP_EOL;
		}
		echo '}' . PHP_EOL;
	}
	public function pack()
	{
		global $TYPEs;
		$return = '';
		$packFormat = $this::parsePackFormat();
		$propertyNumber = -1;
		echo $TYPEs[$this->Type] . ' Object {' . PHP_EOL;
		foreach ($this as $property => $value)
		{
			$pkFnkFormat = $packFormat[++$propertyNumber];
			echo "\t{$pkFnkFormat}\t{$property}\t= " . var_export($this->$property, TRUE) . PHP_EOL;
			if ($pkFnkFormat == 'x')
			{
				$return .= 0; # NULL & 0 are the same thing in Binary (00000000) and Hex (x00), so NULL == 0.
			}
			else
			{
				$return .= pack($pkFnkFormat, $this->$property);
			}
		}
		echo '} Size, ' . strLen($return) . ' Bytes.' . PHP_EOL;
		return $return;
	}
	public function parseUnpackFormat()
	{
		$return = array();
		$elements = split('/', $this::UNPACK);
		foreach ($elements as $element)
		{
			for ($i = 1; is_numeric($element{$i}); ++$i) {}
			$dataType = substr($element, 0, $i);
			$dataName = substr($element, $i);
			$return[$dataName] = $dataType;
		}
		return $return;
	}
	public function parsePackFormat()
	{
		$format = $this::PACK; # It does not like using $this::PACK directly.
		$elements = array();
		for ($i = 0, $j = 1, $k = strLen($format); $i < $k; ++$i, ++$j)
		{
			if (is_string($format{$i}) && !isset($format{$j}) || !is_numeric($format{$j}))
				$elements[] = $format{$i};
			else
			{
				while (isset($format[$j]) && is_numeric($format[$j]))
					++$j;
				$elements[] = substr($format, $i, $j - $i);
				$i = $j - 1;
			}
		}
		return $elements;
	}
}

/* End of PRISM PACKET HEADER */

#ifndef _ISPACKETS_H_
#define _ISPACKETS_H_
/////////////////////

// InSim for Live for Speed : 0.5Z

// InSim allows communication between up to 8 external programs and LFS.

// TCP or UDP packets can be sent in both directions, LFS reporting various
// things about its state, and the external program requesting info and
// controlling LFS with special packets, text commands or keypresses.

// NOTE : This text file was written with a TAB size equal to 4 spaces.


// INSIM VERSION NUMBER (updated for version 0.5X)
// ====================

/* const int INSIM_VERSION = 4; */
define('INSIM_VERSION', 4);

// CHANGES in version 0.5Z (compatible so no change to INSIM_VERSION)
// =======

// NLP / MCI packets are now output at regular intervals
// CCI_LAG bit added to the CompCar structure


// TYPES : (all multi-byte types are PC style - lowest byte first)
// =====

// type			machine byte type			php pack / unpack type
// char			1-byte character			c
// byte			1-byte unsigned integer		C
// word			2-byte unsigned integer		v
// short		2-byte signed integer		s
// unsigned		4-byte unsigned integer		V
// int			4-byte signed integerz		l
// float		4-byte float				f
/* string		var-byte array of charaters	a		*/

// RaceLaps (rl) : (various meanings depending on range)

// 0       : practice
// 1-99    : number of laps...   laps  = rl
// 100-190 : 100 to 1000 laps... laps  = (rl - 100) * 10 + 100
// 191-238 : 1 to 48 hours...    hours = rl - 190


// InSim PACKETS
// =============

// All InSim packets use a four byte header

// Size : total packet size - a multiple of 4
// Type : packet identifier from the ISP_ enum (see below)
// ReqI : non zero if the packet is a packet request or a reply to a request
// Data : the first data byte


// INITIALISING InSim
// ==================

// To initialise the InSim system, type into LFS : /insim xxxxx
// where xxxxx is the TCP and UDP port you want LFS to open.

// OR start LFS with the command line option : LFS /insim=xxxxx
// This will make LFS listen for packets on that TCP and UDP port.


// TO START COMMUNICATION
// ======================

// TCP : Connect to LFS using a TCP connection, then send this packet :
// UDP : No connection required, just send this packet to LFS :

class IS_ISI extends struct // InSim Init - packet to initialise the InSim system
{
	const PACK = 'CCCxvvxCva16a16';
	const UNPACK = 'CSize/CType/CReqI/CZero/vUDPPort/vFlags/CSp0/CPrefix/vInterval/a16Admin/a16IName';

	public $Size = 44;		// 44
	public $Type = ISP_ISI;// always ISP_ISI
	public $ReqI;			// If non-zero LFS will send an IS_VER packet
	public $Zero = 0;	// 0

	public $UDPPort;		// Port for UDP replies from LFS (0 to 65535)
	public $Flags;			// Bit flags for options (see below)

	public $Sp0 = 0;		// 0
	public $Prefix;			// Special host message prefix character
	public $Interval;		// Time in ms between NLP or MCI (0 = none)

	public $Admin;		// Admin password (if set in LFS)
	public $IName;		// A short name for your program
};

// NOTE 1) UDPPort field when you connect using UDP :

// zero     : LFS sends all packets to the port of the incoming packet
// non-zero : LFS sends all packets to the specified UDPPort

// NOTE 2) UDPPort field when you connect using TCP :

// zero     : LFS sends NLP / MCI packets using your TCP connection
// non-zero : LFS sends NLP / MCI packets to the specified UDPPort

// NOTE 3) Flags field (set the relevant bits to turn on the option) :

define('ISF_RES_0',		1); // bit 0 : spare
define('ISF_RES_1',		2); // bit 1 : spare
define('ISF_LOCAL',		4); // bit 2 : guest or single player
define('ISF_MSO_COLS',	8); // bit 3 : keep colours in MSO text
define('ISF_NLP',		16);// bit 4 : receive NLP packets
define('ISF_MCI',		32);// bit 5 : receive MCI packets
$ISF = array(ISF_RES_0 => 'ISF_RES_0', ISF_RES_1 => 'ISF_RES_1', ISF_LOCAL => 'ISF_LOCAL', ISF_MSO_COLS => 'ISF_MSO_COLS', ISF_NLP => 'ISF_NLP', ISF_MCI => 'ISF_MCI');

// In most cases you should not set both ISF_NLP and ISF_MCI flags
// because all IS_NLP information is included in the IS_MCI packet.

// The ISF_LOCAL flag is important if your program creates buttons.
// It should be set if your program is not a host control system.
// If set, then buttons are created in the local button area, so
// avoiding conflict with the host buttons and allowing the user
// to switch them with SHIFT+B rather than SHIFT+I.

// NOTE 4) Prefix field, if set when initialising InSim on a host :

// Messages typed with this prefix will be sent to your InSim program
// on the host (in IS_MSO) and not displayed on anyone's screen.


// ENUMERATIONS FOR PACKET TYPES
// =============================

// the second byte of any packet is one of these
#define('ISP_NONE',	0);	//  0					: not used
define('ISP_ISI',	1);	//  1 - instruction		: insim initialise
define('ISP_VER',	2);	//  2 - info			: version info
define('ISP_TINY',	3);	//  3 - both ways		: multi purpose
define('ISP_SMALL',	4);	//  4 - both ways		: multi purpose
define('ISP_STA',	5);	//  5 - info			: state info
define('ISP_SCH',	6);	//  6 - instruction		: single character
define('ISP_SFP',	7);	//  7 - instruction		: state flags pack
define('ISP_SCC',	8);	//  8 - instruction		: set car camera
define('ISP_CPP',	9);	//  9 - both ways		: cam pos pack
define('ISP_ISM',	10);// 10 - info			: start multiplayer
define('ISP_MSO',	11);// 11 - info			: message out
define('ISP_III',	12);// 12 - info			: hidden /i message
define('ISP_MST',	13);// 13 - instruction		: type message or /command
define('ISP_MTC',	14);// 14 - instruction		: message to a connection
define('ISP_MOD',	15);// 15 - instruction		: set screen mode
define('ISP_VTN',	16);// 16 - info			: vote notification
define('ISP_RST',	17);// 17 - info			: race start
define('ISP_NCN',	18);// 18 - info			: new connection
define('ISP_CNL',	19);// 19 - info			: connection left
define('ISP_CPR',	20);// 20 - info			: connection renamed
define('ISP_NPL',	21);// 21 - info			: new player (joined race)
define('ISP_PLP',	22);// 22 - info			: player pit (keeps slot in race)
define('ISP_PLL',	23);// 23 - info			: player leave (spectate - loses slot)
define('ISP_LAP',	24);// 24 - info			: lap time
define('ISP_SPX',	25);// 25 - info			: split x time
define('ISP_PIT',	26);// 26 - info			: pit stop start
define('ISP_PSF',	27);// 27 - info			: pit stop finish
define('ISP_PLA',	28);// 28 - info			: pit lane enter / leave
define('ISP_CCH',	29);// 29 - info			: camera changed
define('ISP_PEN',	30);// 30 - info			: penalty given or cleared
define('ISP_TOC',	31);// 31 - info			: take over car
define('ISP_FLG',	32);// 32 - info			: flag (yellow or blue)
define('ISP_PFL',	33);// 33 - info			: player flags (help flags)
define('ISP_FIN',	34);// 34 - info			: finished race
define('ISP_RES',	35);// 35 - info			: result confirmed
define('ISP_REO',	36);// 36 - both ways		: reorder (info or instruction)
define('ISP_NLP',	37);// 37 - info			: node and lap packet
define('ISP_MCI',	38);// 38 - info			: multi car info
define('ISP_MSX',	39);// 39 - instruction		: type message
define('ISP_MSL',	40);// 40 - instruction		: message to local computer
define('ISP_CRS',	41);// 41 - info			: car reset
define('ISP_BFN',	42);// 42 - both ways		: delete buttons / receive button requests
define('ISP_AXI',	43);// 43 - info			: autocross layout information
define('ISP_AXO',	44);// 44 - info			: hit an autocross object
define('ISP_BTN',	45);// 45 - instruction		: show a button on local or remote screen
define('ISP_BTC',	46);// 46 - info			: sent when a user clicks a button
define('ISP_BTT',	47);// 47 - info			: sent after typing into a button
define('ISP_RIP',	48);// 48 - both ways		: replay information packet
define('ISP_SSH',	49);// 49 - both ways		: screenshot
$ISP = array(/*0 => 'ISP_NONE',*/ ISP_ISI => 'ISP_ISI', ISP_VER => 'ISP_VER', ISP_TINY => 'ISP_TINY', ISP_SMALL => 'ISP_SMALL', ISP_STA => 'ISP_STA', ISP_SCH => 'ISP_SCH', ISP_SFP => 'ISP_SFP', ISP_SCC => 'ISP_SCC', ISP_CPP => 'ISP_CPP', ISP_ISM => 'ISP_ISM', ISP_MSO => 'ISP_MSO', ISP_III => 'ISP_III', ISP_MST => 'ISP_MST', ISP_MTC => 'ISP_MTC', ISP_MOD => 'ISP_MOD', ISP_VTN => 'ISP_VTN', ISP_RST => 'ISP_RST', ISP_MTC => 'ISP_MTC', ISP_CNL => 'ISP_CNL', ISP_CPR => 'ISP_CPR', ISP_NPL => 'ISP_NPL', ISP_PLP => 'ISP_PLP', ISP_PLL => 'ISP_PLL', ISP_LAP => 'ISP_LAP', ISP_SPX => 'ISP_SPX', ISP_PIT => 'ISP_PIT', ISP_PSF => 'ISP_PSF', ISP_PLA => 'ISP_PLA', ISP_CCH => 'ISP_CCH', ISP_PEN => 'ISP_PEN', ISP_TOC => 'ISP_TOC', ISP_FLG => 'ISP_FLG', ISP_PFL => 'ISP_PFL', ISP_FIN => 'ISP_FIN', ISP_RES => 'ISP_RES', ISP_REO => 'ISP_REO', ISP_NPL => 'ISP_NPL', ISP_MCI => 'ISP_MCI', ISP_MSX => 'ISP_MSX', ISP_MSL => 'ISP_MSL', ISP_CRS => 'ISP_CRS', ISP_BFN => 'ISP_BFN', ISP_AXI => 'ISP_AXI', ISP_AXO => 'ISP_AXO', ISP_BTN => 'ISP_BTN', ISP_BTC => 'ISP_BTC', ISP_BTT => 'ISP_BTT', ISP_RIP => 'ISP_RIP', ISP_SSH => 'ISP_SSH');

// the fourth byte of an IS_TINY packet is one of these
define('TINY_NONE',	0);	//  0 - keep alive		: see "maintaining the connection"
define('TINY_VER',	1);	//  1 - info request	: get version
define('TINY_CLOSE',2);	//  2 - instruction		: close insim
define('TINY_PING',	3);	//  3 - ping request	: external progam requesting a reply
define('TINY_REPLY',4);	//  4 - ping reply		: reply to a ping request
define('TINY_VTC',	5);	//  5 - info			: vote cancelled
define('TINY_SCP',	6);	//  6 - info request	: send camera pos
define('TINY_SST',	7);	//  7 - info request	: send state info
define('TINY_GTH',	8);	//  8 - info request	: get time in hundredths (i.e. SMALL_RTP)
define('TINY_MPE',	9);	//  9 - info			: multi player end
define('TINY_ISM',	10);// 10 - info request	: get multiplayer info (i.e. ISP_ISM)
define('TINY_REN',	11);// 11 - info			: race end (return to game setup screen)
define('TINY_CLR',	12);// 12 - info			: all players cleared from race
define('TINY_NCN',	13);// 13 - info request	: get all connections
define('TINY_NPL',	14);// 14 - info request	: get all players
define('TINY_RES',	15);// 15 - info request	: get all results
define('TINY_NLP',	16);// 16 - info request	: send an IS_NLP
define('TINY_MCI',	17);// 17 - info request	: send an IS_MCI
define('TINY_REO',	18);// 18 - info request	: send an IS_REO
define('TINY_RST',	19);// 19 - info request	: send an IS_RST
define('TINY_AXI',	20);// 20 - info request	: send an IS_AXI - AutoX Info
define('TINY_AXC',	21);// 21 - info			: autocross cleared
define('TINY_RIP',	22);// 22 - info request	: send an IS_RIP - Replay Information Packet
$TINY = array(TINY_NONE => 'TINY_NONE', TINY_VER => 'TINY_VER', TINY_CLOSE => 'TINY_CLOSE', TINY_PING => 'TINY_PING', TINY_REPLY => 'TINY_REPLY', TINY_VTC => 'TINY_VTC', TINY_SCP => 'TINY_SCP', TINY_SST => 'TINY_SST', TINY_GTH => 'TINY_GTH', TINY_MPE => 'TINY_MPE', TINY_ISM => 'TINY_ISM', TINY_REN => 'TINY_REN', TINY_CLR => 'TINY_CLR', TINY_NCN => 'TINY_NCN', TINY_NPL => 'TINY_NPL', TINY_RES => 'TINY_RES', TINY_NLP => 'TINY_NLP', TINY_MCI => 'TINY_MCI', TINY_REO => 'TINY_REO', TINY_RST => 'TINY_RST', TINY_AXI => 'TINY_AXI', TINY_AXC => 'TINY_AXC', TINY_RIP => 'TINY_RIP');

// the fourth byte of an IS_SMALL packet is one of these
define('SMALL_NONE',0);	//  0					: not used
define('SMALL_SSP',	1);	//  1 - instruction		: start sending positions
define('SMALL_SSG',	2);	//  2 - instruction		: start sending gauges
define('SMALL_VTA',	3);	//  3 - report			: vote action
define('SMALL_TMS',	4);	//  4 - instruction		: time stop
define('SMALL_STP',	5);	//  5 - instruction		: time step
define('SMALL_RTP',	6);	//  6 - info			: race time packet (reply to GTH)
define('SMALL_NLI',	7);	//  7 - instruction		: set node lap interval
$SMALL = array(SMALL_NONE => 'SMALL_NONE', SMALL_SSP => 'SMALL_SSP', SMALL_SSG => 'SMALL_SSG', SMALL_VTA => 'SMALL_VTA', SMALL_TMS => 'SMALL_TMS', SMALL_STP => 'SMALL_STP', SMALL_RTP => 'SMALL_RTP', SMALL_NLI => 'SMALL_NLI');


// GENERAL PURPOSE PACKETS - IS_TINY (4 bytes) and IS_SMALL (8 bytes)
// =======================

// To avoid defining several packet structures that are exactly the same, and to avoid
// wasting the ISP_ enumeration, IS_TINY is used at various times when no additional data
// other than SubT is required.  IS_SMALL is used when an additional integer is needed.

// IS_TINY - used for various requests, replies and reports

class IS_TINY extends struct // General purpose 4 byte packet
{
	const PACK = 'CCCC';
	const UNPACK = 'CSize/CType/CReqI/CSubT';

	public	$Size = 4;			// always 4
	public	$Type = ISP_TINY;	// always ISP_TINY
	public	$ReqI;				// 0 unless it is an info request or a reply to an info request
	public	$SubT;				// subtype, from TINY_ enumeration (e.g. TINY_RACE_END)
}

// IS_SMALL - used for various requests, replies and reports

class IS_SMALL extends struct // General purpose 8 byte packet
{
	const PACK = 'CCCCV';
	const UNPACK = 'CSize/CType/CReqI/CSubT/VUVal';

	public $Size = 8;			// always 8
	public $Type = ISP_SMALL;	// always ISP_SMALL
	public $ReqI;				// 0 unless it is an info request or a reply to an info request
	public $SubT;				// subtype, from SMALL_ enumeration (e.g. SMALL_SSP)

	public $UVal;				// value (e.g. for SMALL_SSP this would be the OutSim packet rate)
};


// VERSION REQUEST
// ===============

// It is advisable to request version information as soon as you have connected, to
// avoid problems when connecting to a host with a later or earlier version.  You will
// be sent a version packet on connection if you set ReqI in the IS_ISI packet.

// This version packet can be sent on request :

class IS_VER extends struct // VERsion
{
	const PACK = 'CCCxa8a6v';
	const UNPACK = 'CSize/CType/CReqI/CZero/a8Version/a6Product/vInSimVer';

	public $Size = 20;					// 20
	public $Type = ISP_VER;			// ISP_VERSION
	public $ReqI;						// ReqI as received in the request packet
	public $Zero;

	public $Version;					// LFS version, e.g. 0.3G
	public $Product;					// Product : DEMO or S1
	public $InSimVer = INSIM_VERSION;// InSim Version : increased when InSim packets change
};

// To request an InSimVersion packet at any time, send this IS_TINY :

// ReqI : non-zero		(returned in the reply)
// SubT : TINY_VER		(request an IS_VER)


// CLOSING InSim
// =============

// You can send this IS_TINY to close the InSim connection to your program :

// ReqI : 0
// SubT : TINY_CLOSE	(close this connection)

// Another InSimInit packet is then required to start operating again.

// You can shut down InSim completely and stop it listening at all by typing /insim=0
// into LFS (or send a MsgTypePack to do the same thing).


// MAINTAINING THE CONNECTION - IMPORTANT
// ==========================

// If InSim does not receive a packet for 70 seconds, it will close your connection.
// To open it again you would need to send another InSimInit packet.

// LFS will send a blank IS_TINY packet like this every 30 seconds :

// ReqI : 0
// SubT : TINY_NONE		(keep alive packet)

// You should reply with a blank IS_TINY packet :

// ReqI : 0
// SubT : TINY_NONE		(has no effect other than resetting the timeout)

// NOTE : If you want to request a reply from LFS to check the connection
// at any time, you can send this IS_TINY :

// ReqI : non-zero		(returned in the reply)
// SubT : TINY_PING		(request a TINY_REPLY)

// LFS will reply with this IS_TINY :

// ReqI : non-zero		(as received in the request packet)
// SubT : TINY_REPLY	(reply to ping)


// STATE REPORTING AND REQUESTS
// ============================

// LFS will send a StatePack any time the info in the StatePack changes.

class IS_STA extends struct // STAte
{
	const PACK = 'CCCxfvCCCCCCCCxxa6CC';
	const UNPACK = 'CSize/CType/CReqI/CZero/fReplaySpeed/vFlags/CInGameCam/CViewPLID/CNumP/CNumConns/CNumFinished/CRaceInProg/CQualMins/CRaceLaps/CSpare2/CSpare3/a6Track/CWeather/CWind';

	public $Size = 28;		// 28
	public $Type = ISP_STA;// ISP_STA
	public $ReqI;			// ReqI if replying to a request packet
	public $Zero;

	public $ReplaySpeed;	// 4-byte float - 1.0 is normal speed

	public $Flags;			// ISS state flags (see below)
	public $InGameCam;		// Which type of camera is selected (see below)
	public $ViewPLID;		// Unique ID of viewed player (0 = none)

	public $NumP;			// Number of players in race
	public $NumConns;		// Number of connections including host
	public $NumFinished;	// Number finished or qualified
	public $RaceInProg;		// 0 - no race / 1 - race / 2 - qualifying

	public $QualMins;
	public $RaceLaps;		// see "RaceLaps" near the top of this document
	public $Spare2;
	public $Spare3;

	public $Track;		// short name for track e.g. FE2R
	public $Weather;		// 0,1,2...
	public $Wind;			// 0=off 1=weak 2=strong
};

// InGameCam is the in game selected camera mode (which is
// still selected even if LFS is actually in SHIFT+U mode).
// For InGameCam's values, see "View identifiers" below.

// ISS state flags

define('ISS_GAME',			1);		// in game (or MPR)
define('ISS_REPLAY',		2);		// in SPR
define('ISS_PAUSED',		4);		// paused
define('ISS_SHIFTU',		8);		// SHIFT+U mode
define('ISS_SHIFTU_HIGH',	16);	// HIGH view
define('ISS_SHIFTU_FOLLOW',	32);	// following car
define('ISS_SHIFTU_NO_OPT',	64);	// SHIFT+U buttons hidden
define('ISS_SHOW_2D',		128);	// showing 2d display
define('ISS_FRONT_END',		256);	// entry screen
define('ISS_MULTI',			512);	// multiplayer mode
define('ISS_MPSPEEDUP',		1024);	// multiplayer speedup option
define('ISS_WINDOWED',		2048);	// LFS is running in a window
define('ISS_SOUND_MUTE',	4096);	// sound is switched off
define('ISS_VIEW_OVERRIDE',	8192);	// override user view
define('ISS_VISIBLE',		16384);	// InSim buttons visible
$ISS = array(ISS_GAME => 'ISS_GAME', ISS_REPLAY => 'ISS_REPLAY', ISS_PAUSED => 'ISS_PAUSED', ISS_SHIFTU => 'ISS_SHIFTU', ISS_SHIFTU_HIGH => 'ISS_SHIFTU_HIGH', ISS_SHIFTU_FOLLOW => 'ISS_SHIFTU_FOLLOW', ISS_SHIFTU_NO_OPT => 'ISS_SHIFTU_NO_OPT', ISS_SHOW_2D => 'ISS_SHOW_2D', ISS_FRONT_END => 'ISS_FRONT_END', ISS_MULTI => 'ISS_MULTI', ISS_MPSPEEDUP => 'ISS_MPSPEEDUP', ISS_WINDOWED => 'ISS_WINDOWED', ISS_SOUND_MUTE => 'ISS_SOUND_MUTE', ISS_VIEW_OVERRIDE => 'ISS_VIEW_OVERRIDE', ISS_VISIBLE => 'ISS_VISIBLE');

// To request a StatePack at any time, send this IS_TINY :

// ReqI : non-zero		(returned in the reply)
// SubT : TINY_SST		(Send STate)

// Setting states

// These states can be set by a special packet :

// ISS_SHIFTU_FOLLOW	- following car
// ISS_SHIFTU_NO_OPT	- SHIFT+U buttons hidden
// ISS_SHOW_2D			- showing 2d display
// ISS_MPSPEEDUP		- multiplayer speedup option
// ISS_SOUND_MUTE		- sound is switched off

class IS_SFP extends struct // State Flags Pack
{
	const PACK = 'CCCxvCx';
	const UNPACK = 'CSize/CType/CReqI/CZero/vFlag/COffOn/CSp3';

	public $Size = 8;		// 8
	public $Type = ISP_SFP;// ISP_SFP
	public $ReqI;			// 0
	public $Zero;

	public $Flag;			// the state to set
	public $OffOn;			// 0 = off / 1 = on
	public $Sp3;			// spare
};

// Other states must be set by using keypresses or messages (see below)


// SCREEN MODE
// ===========

// You can send this packet to LFS to set the screen mode :

class IS_MOD extends struct // MODe : send to LFS to change screen mode
{
	const PACK = 'CCxxllll';
	const UNPACK = 'CSize/CType/CReqI/CZero/lBits16/lRR/lWidth/lHeight';

	public $Size = 20;		// 20
	public $Type = ISP_MOD;// ISP_MOD
	public $ReqI;		// 0
	public $Zero;

	public $Bits16;			// set to choose 16-bit
	public $RR;				// refresh rate - zero for default
	public $Width;			// 0 means go to window
	public $Height;			// 0 means go to window
};

// The refresh rate actually selected by LFS will be the highest available rate
// that is less than or equal to the specified refresh rate.  Refresh rate can
// be specified as zero in which case the default refresh rate will be used.

// If Width and Height are both zero, LFS will switch to windowed mode.


// TEXT MESSAGES AND KEY PRESSES
// ==============================

// You can send 64-byte text messages to LFS as if the user had typed them in.
// Messages that appear on LFS screen (up to 128 bytes) are reported to the
// external program.  You can also send simulated keypresses to LFS.

// MESSAGES OUT (FROM LFS)
// ------------

class IS_MSO extends struct // MSg Out - system messages and user messages 
{
	const PACK = 'CCxxCCCCa128';
	const UNPACK = 'CSize/CType/CReqI/CZero/CUCID/CPLID/CUserType/CTextStart/a128Msg';

	public $Size = 136;	// 136
	public $Type = ISP_MSO;// ISP_MSO
	public $ReqI;		// 0
	public $Zero;

	public $UCID;			// connection's unique id (0 = host)
	public $PLID;			// player's unique id (if zero, use UCID)
	public $UserType;		// set if typed by a user (see User Values below) 
	public $TextStart;		// first character of the actual text (after player name)

	public $Msg;
};

// User Values (for UserType byte)

define('MSO_SYSTEM',	0);	// 0 - system message
define('MSO_USER',		1);	// 1 - normal visible user message
define('MSO_PREFIX',	2);	// 2 - hidden message starting with special prefix (see ISI)
define('MSO_O',			3);	// 3 - hidden message typed on local pc with /o command
define('MSO_NUM',		4);
$MSO = array(MSO_SYSTEM => 'MSO_SYSTEM', MSO_USER => 'MSO_USER', MSO_PREFIX => 'MSO_PREFIX', MSO_O => 'MSO_O', MSO_NUM => 'MSO_NUM');

// NOTE : Typing "/o MESSAGE" into LFS will send an IS_MSO with UserType = MSO_O

class IS_III extends struct // InsIm Info - /i message from user to host's InSim
{
	const PACK = 'CCxxCCxxa64';
	const UNPACk = 'CSize/CType/CReqI/CZero/CUCID/CPLID/CSp2/CSp3/a64Msg';

	public $Size;		// 72
	public $Type;		// ISP_III
	public $ReqI;	// 0
	public $Zero;

	public $UCID;		// connection's unique id (0 = host)
	public $PLID;		// player's unique id (if zero, use UCID)
	public $Sp2;
	public $Sp3;

	public $Msg;
};

// MESSAGES IN (TO LFS)
// -----------

class IS_MST extends struct // MSg Type - send to LFS to type message or command
{
	const PACK = 'CCxxa64';
	const UNPACK = 'CSize/CType/CReqI/CZero/a64Msg';

	public $Size = 68;		// 68
	public $Type = ISP_MST;// ISP_MST
	public $ReqI;		// 0
	public $Zero;

	public $Msg;		// last byte must be zero
};

class IS_MSX extends struct // MSg eXtended - like MST but longer (not for commands)
{
	const PACK = 'CCxxa96';
	const UNPACK = 'CSize/CType/CReqI/CZero/a96Msg';

	public $Size = 100;	// 100
	public $Type = ISP_MSX;// ISP_MSX
	public $ReqI;		// 0
	public $Zero;

	public $Msg;		// last byte must be zero
};

class IS_MSL extends struct // MSg Local - message to appear on local computer only
{
	const PACK = 'CCxCa128';
	const UNPACK = 'CSize/CType/CReqI/CSound/a128Msg';

	public $Size = 132;			// 132
	public $Type = ISP_MSL;		// ISP_MSL
	public $ReqI = 0;			// 0
	public $Sound = SND_SILENT;	// sound effect (see Message Sounds below)

	public $Msg;				// last byte must be zero
};

class IS_MTC extends struct // Msg To Connection - hosts only - send to a connection or a player
{
	const PACK = 'CCxxCCxxa64';
	const UNPACK = 'CSize/CType/CReqI/CZero/CUCID/CPLID/CSp2/CSp3/a64Msg';

	public $Size = 72;		// 72
	public $Type = ISP_MTC;	// ISP_MTC
	public $ReqI;			// 0
	public $Zero;

	public $UCID;			// connection's unique id (0 = host)
	public $PLID;			// player's unique id (if zero, use UCID)
	public $Sp2;
	public $Sp3;

	public $Msg;			// last byte must be zero
};

// Message Sounds (for Sound byte)

define('SND_SILENT', 	0);
define('SND_MESSAGE',	1);
define('SND_SYSMESSAGE',2);
define('SND_INVALIDKEY',3);
define('SND_ERROR', 	4);
define('SND_NUM',		5);
$SND = array(SND_SILENT => 'SND_SILENT', SND_MESSAGE => 'SND_MESSAGE', SND_SYSMESSAGE => 'SND_SYSMESSAGE', SND_INVALIDKEY => 'SND_INVALIDKEY', SND_ERROR => 'SND_ERROR', SND_NUM => 'SND_NUM');

// You can send individual key presses to LFS with the IS_SCH packet.
// For standard keys (e.g. V and H) you should send a capital letter.
// This does not work with some keys like F keys, arrows or CTRL keys.
// You can also use IS_MST with the /press /shift /ctrl /alt commands.

class IS_SCH extends struct // Single CHaracter
{
	const PACK = 'CCxxCCxx';
	const UNPACK = 'CSize/CType/CReqI/CZero/CCharB/CFlags/CSpare2/CSpare3';

	public $Size = 8;		// 8
	public $Type = ISP_SCH;// ISP_SCH
	public $ReqI;		// 0
	public $Zero;

	public $CharB;			// key to press
	public $Flags;			// bit 0 : SHIFT / bit 1 : CTRL
	public $Spare2;
	public $Spare3;
};


// MULTIPLAYER NOTIFICATION
// ========================

// LFS will send this packet when a host is started or joined :

class IS_ISM extends struct // InSim Multi
{
	const PACK = 'CCCxCxxxa32';
	const UNPACK = 'CSize/CType/CReqI/CZero/CHost/CSp1/CSp2/CSp3/a32HName';

	public $Size = 40;		// 40
	public $Type = ISP_ISM;// ISP_ISM
	public $ReqI;			// usually 0 / or if a reply : ReqI as received in the TINY_ISM
	public $Zero;

	public $Host;		// 0 = guest / 1 = host
	public $Sp1;
	public $Sp2;
	public $Sp3;

	public $HName;	// the name of the host joined or started
};

// On ending or leaving a host, LFS will send this IS_TINY :

// ReqI : 0
// SubT : TINY_MPE		(MultiPlayerEnd)

// To request an IS_ISM packet at any time, send this IS_TINY :

// ReqI : non-zero		(returned in the reply)
// SubT : TINY_ISM		(request an IS_ISM)

// NOTE : If LFS is not in multiplayer mode, the host name in the ISM will be empty.


// VOTE NOTIFY AND CANCEL
// ======================

// LFS notifies the external program of any votes to restart or qualify

// The Vote Actions are defined as :

define('VOTE_NONE',		0);	// 0 - no vote
define('VOTE_END',		1);	// 1 - end race
define('VOTE_RESTART',	2);	// 2 - restart
define('VOTE_QUALIFY',	3);	// 3 - qualify
define('VOTE_NUM',		4);
$VOTE = array(VOTE_NONE => 'VOTE_NONE', VOTE_END => 'VOTE_END', VOTE_RESTART => 'VOTE_RESTART', VOTE_QUALIFY => 'VOTE_QUALIFY', VOTE_NUM => 'VOTE_NUM');

class IS_VTN extends struct // VoTe Notify
{
	const PACK = 'CCxxCCxx';
	const UNPACK = 'CSize/CType/CReqI/CZero/CUCID/CAction/CSpare2/CSpare3';

	public $Size = 8;		// 8
	public $Type = ISP_VTN;// ISP_VTN
	public $ReqI;		// 0
	public $Zero;

	public $UCID;			// connection's unique id
	public $Action;			// VOTE_X (Vote Action as defined above)
	public $Spare2;
	public $Spare3;
};

// When a vote is cancelled, LFS sends this IS_TINY

// ReqI : 0
// SubT : TINY_VTC		(VoTe Cancelled)

// When a vote is completed, LFS sends this IS_SMALL

// ReqI : 0
// SubT : SMALL_VTA  	(VoTe Action)
// UVal : action 		(VOTE_X - Vote Action as defined above)

// You can instruct LFS host to cancel a vote using an IS_TINY

// ReqI : 0
// SubT : TINY_VTC		(VoTe Cancel)


// RACE TRACKING
// =============

// In LFS there is a list of connections AND a list of players in the race
// Some packets are related to connections, some players, some both

// If you are making a multiplayer InSim program, you must maintain two lists
// You should use the unique identifier UCID to identify a connection

// Each player has a unique identifier PLID from the moment he joins the race, until he
// leaves.  It's not possible for PLID and UCID to be the same thing, for two reasons :

// 1) there may be more than one player per connection if AI drivers are used
// 2) a player can swap between connections, in the case of a driver swap (IS_TOC)

// When all players are cleared from race (e.g. /clear) LFS sends this IS_TINY

// ReqI : 0
// SubT : TINY_CLR		(CLear Race)

// When a race ends (return to game setup screen) LFS sends this IS_TINY

// ReqI : 0
// SubT : TINY_REN  	(Race ENd)

// You can instruct LFS host to cancel a vote using an IS_TINY

// ReqI : 0
// SubT : TINY_VTC		(VoTe Cancel)

// The following packets are sent when the relevant events take place :

class IS_RST extends struct // Race STart
{
	const PACK = 'CCCxCCCxa6CCvvvvvv';
	const UNPACK = 'CSize/CType/CReqI/CZero/CRaceLaps/CQualMins/CNumP/CSpare/a6Track/CWeather/CWind/vFlags/vNumNodes/vFinish/vSplit1/vSplit2/vSplit3';

	public $Size = 28;		// 28
	public $Type = ISP_RST;// ISP_RST
	public $ReqI;			// 0 unless this is a reply to an TINY_RST request
	public $Zero;

	public $RaceLaps;		// 0 if qualifying
	public $QualMins;		// 0 if race
	public $NumP;			// number of players in race
	public $Spare;

	public $Track;		// short track name
	public $Weather;
	public $Wind;

	public $Flags;			// race flags (must pit, can reset, etc - see below)
	public $NumNodes;		// total number of nodes in the path
	public $Finish;			// node index - finish line
	public $Split1;			// node index - split 1
	public $Split2;			// node index - split 2
	public $Split3;			// node index - split 3
};

// To request an IS_RST packet at any time, send this IS_TINY :

// ReqI : non-zero		(returned in the reply)
// SubT : TINY_RST		(request an IS_RST)

class IS_NCN extends struct // New ConN
{
	const PACK = 'CCCCa24a24CCCx';
	const UNPACK = 'CSize/CType/CReqI/CUCID/a24UName/a24PName/CAdmin/CTotal/CFlags/CSp3';

	public $Size = 65;		// 56
	public $Type = ISP_NCN;// ISP_NCN
	public $ReqI;			// 0 unless this is a reply to a TINY_NCN request
	public $UCID;			// new connection's unique id (0 = host)

	public $UName;		// username
	public $PName;		// nickname

	public $Admin;			// 1 if admin
	public $Total;			// number of connections including host
	public $Flags;			// bit 2 : remote
	public $Sp3;
};

class IS_CNL extends struct // ConN Leave
{
	const PACK = 'CCxCCCxx';
	const UNPACK = 'CSize/CType/CReqI/CUCID/CReason/CTotal/CSp2/CSp3';

	public $Size = 8;		// 8
	public $Type = ISP_CNL;// ISP_CNL
	public $ReqI;		// 0
	public $UCID;			// unique id of the connection which left

	public $Reason;			// leave reason (see below)
	public $Total;			// number of connections including host
	public $Sp2;
	public $Sp3;
};

class IS_CPR extends struct // Conn Player Rename
{
	const PACK = 'CCxCa24a8';
	const UNPACK = 'CSize/CType/CReqI/CUCID/a24PName/a8Plate';

	public $Size = 36;		// 36
	public $Type = ISP_CPR;// ISP_CPR
	public $ReqI;		// 0
	public $UCID;			// unique id of the connection

	public $PName;		// new name
	public $Plate;		// number plate - NO ZERO AT END!
};

class IS_NPL extends struct // New PLayer joining race (if PLID already exists, then leaving pits)
{
	const PACK = 'CCCCCCva24a8a4a16C4CCCClCCxx';
	const UNPACK = 'CSize/CType/CReqI/CPLID/CUCID/CPType/vFlags/a24PName/a8Plate/a4CName/a16SName/C4Tyres/CH_Mass/CH_TRes/CModel/CPass/lSpare/CSetF/CNumP/CSp2/CSp3';

	public $Size = 76;		// 76
	public $Type = ISP_NPL;// ISP_NPL
	public $ReqI;			// 0 unless this is a reply to an TINY_NPL request
	public $PLID;			// player's newly assigned unique id

	public $UCID;			// connection's unique id
	public $PType;			// bit 0 : female / bit 1 : AI / bit 2 : remote
	public $Flags;			// player flags

	public $PName;		// nickname
	public $Plate;		// number plate - NO ZERO AT END!

	public $CName;		// car name
	public $SName;		// skin name - MAX_CAR_TEX_NAME
	public $Tyres;		// compounds

	public $H_Mass;			// added mass (kg)
	public $H_TRes;			// intake restriction
	public $Model;			// driver model
	public $Pass;			// passengers byte

	public $Spare;

	public $SetF;			// setup flags (see below)
	public $NumP;			// number in race (same when leaving pits, 1 more if new)
	public $Sp2;
	public $Sp3;
};

// NOTE : PType bit 0 (female) is not reported on dedicated host as humans are not loaded
// You can use the driver model byte instead if required (and to force the use of helmets)

// Setup flags (for SetF byte)

define('SETF_SYMM_WHEELS',	1);
define('SETF_TC_ENABLE',	2);
define('SETF_ABS_ENABLE',	4);
$SETF = array(SETF_SYMM_WHEELS => 'SETF_SYMM_WHEELS', SETF_TC_ENABLE => 'SETF_TC_ENABLE', SETF_ABS_ENABLE => 'SETF_ABS_ENABLE');

// More...

class IS_PLP extends struct // PLayer Pits (go to settings - stays in player list)
{
	const PACK = 'CCxC';
	const UNPACK = 'CSize/CType/CReqI/CPLID';

	public $Size = 4;		// 4
	public $Type = ISP_PLP;// ISP_PLP
	public $ReqI;		// 0
	public $PLID;			// player's unique id
};

class IS_PLL extends struct // PLayer Leave race (spectate - removed from player list)
{
	const PACK = 'CCxC';
	const UNPACK = 'CSize/CType/CReqI/CPLID';

	public $Size = 4;		// 4
	public $Type = ISP_PLL;// ISP_PLL
	public $ReqI;		// 0
	public $PLID;			// player's unique id
};

class IS_CRS extends struct // Car ReSet
{
	const PACK = 'CCxC';
	const UNPACK = 'CSize/CType/CReqI/CPLID';

	public $Size = 4;		// 4
	public $Type = ISP_CRS;// ISP_CRS
	public $ReqI;		// 0
	public $PLID;			// player's unique id
};

class IS_LAP extends struct // LAP time
{
	const PACK = 'CCxCVVvvxCCx';
	const UNPACK = 'CSize/CType/CReqI/CPLID/VLTime/VETime/vLapsDone/vFlags/CSp0/CPenalty/CNumStops/CSp3';

	public $Size = 20;		// 20
	public $Type = ISP_LAP;// ISP_LAP
	public $ReqI;		// 0
	public $PLID;			// player's unique id

	public $LTime;			// lap time (ms)
	public $ETime;			// total time (ms)

	public $LapsDone;		// laps completed
	public $Flags;			// player flags

	public $Sp0;
	public $Penalty;		// current penalty value (see below)
	public $NumStops;		// number of pit stops
	public $Sp3;
};

class IS_SPX extends struct // SPlit X time
{
	const PACK = 'CCxCVVCCCx';
	const UNPACK = 'CSize/CType/CReqI/CPLID/VSTime/VETime/CSplit/CPenalty/CNumStops/CSp3';

	public $Size = 16;		// 16
	public $Type = ISP_SPX;// ISP_SPX
	public $ReqI;		// 0
	public $PLID;			// player's unique id

	public $STime;			// split time (ms)
	public $ETime;			// total time (ms)

	public $Split;			// split number 1, 2, 3
	public $Penalty;		// current penalty value (see below)
	public $NumStops;		// number of pit stops
	public $Sp3;
};

class IS_PIT extends struct // PIT stop (stop at pit garage)
{
	const PACK = 'CCxCvvxCCxC4VV';
	const UNPACK = 'CSize/CType/CReqI/CPLID/vLapsDone/vFlags/CSp0/CPenalty/CNumStops/CSp3/C4Tyres/VWork/VSpare';

	public $Size = 24;		// 24
	public $Type = ISP_PIT;// ISP_PIT
	public $ReqI;		// 0
	public $PLID;			// player's unique id

	public $LapsDone;		// laps completed
	public $Flags;			// player flags

	public $Sp0;
	public $Penalty;		// current penalty value (see below)
	public $NumStops;		// number of pit stops
	public $Sp3;

	public $Tyres;		// tyres changed

	public $Work;			// pit work
	public $Spare;
};

class IS_PSF extends struct // Pit Stop Finished
{
	const PACK = 'CCxCVV';
	const UNPACK = 'CSize/CType/CReqI/CPLID/VSTime/VSpare';

	public $Size = 12;		// 12
	public $Type = ISP_PSF;// ISP_PSF
	public $ReqI;		// 0
	public $PLID;			// player's unique id

	public $STime;			// stop time (ms)
	public $Spare;
};

class IS_PLA extends struct // Pit LAne
{
	const PACK = 'CCxCCxxx';
	const UNPACK = 'CSize/CType/CReqI/CPLID/CFact/CSp1/CSp2/CSp3';

	public $Size = 8;		// 8
	public $Type = ISP_PLA;// ISP_PLA
	public $ReqI;		// 0
	public $PLID;			// player's unique id

	public $Fact;			// pit lane fact (see below)
	public $Sp1;
	public $Sp2;
	public $Sp3;
};

// IS_CCH : Camera CHange

// To track cameras you need to consider 3 points

// 1) The default camera : VIEW_DRIVER
// 2) Player flags : CUSTOM_VIEW means VIEW_CUSTOM at start or pit exit
// 3) IS_CCH : sent when an existing driver changes camera

class IS_CCH extends struct // Camera CHange
{
	const PACK = 'CCxCCxxx';
	const UNPACK = 'CSize/CType/CReqI/CPLID/CCamera/CSp1/CSp2/CSp3';

	public $Size = 8;		// 8
	public $Type = ISP_CCH;// ISP_CCH
	public $ReqI;		// 0
	public $PLID;			// player's unique id

	public $Camera;			// view identifier (see below)
	public $Sp1;
	public $Sp2;
	public $Sp3;
};

class IS_PEN extends struct // PENalty (given or cleared)
{
	const PACK = 'CCxCCCCx';
	const UNPACK = 'CSize/CType/CReqI/CPLID/COldPen/CNewPen/CReason/CSp3';

	public $Size = 8;		// 8
	public $Type = ISP_PEN;// ISP_PEN
	public $ReqI;		// 0
	public $PLID;			// player's unique id

	public $OldPen;			// old penalty value (see below)
	public $NewPen;			// new penalty value (see below)
	public $Reason;			// penalty reason (see below)
	public $Sp3;
};

class IS_TOC extends struct // Take Over Car
{
	const PACK = 'CCxCCCxx';
	const UNPACK = 'CSize/CType/CReqI/CPLID/COldUCID/CNewUCID/CSp2/CSp3';

	public $Size = 8;		// 8
	public $Type = ISP_TOC;// ISP_TOC
	public $ReqI;		// 0
	public $PLID;			// player's unique id

	public $OldUCID;		// old connection's unique id
	public $NewUCID;		// new connection's unique id
	public $Sp2;
	public $Sp3;
};

class IS_FLG extends struct // FLaG (yellow or blue flag changed)
{
	const PACK = 'CCxCCCCC';
	const UNPACK = 'CSize/CType/CReqI/CPLID/COffOn/CFlag/CCarBehind/CSp3';

	public $Size = 8;		// 8
	public $Type = ISP_FLG;// ISP_FLG
	public $ReqI;		// 0
	public $PLID;			// player's unique id

	public $OffOn;			// 0 = off / 1 = on
	public $Flag;			// 1 = given blue / 2 = causing yellow
	public $CarBehind;		// unique id of obstructed player
	public $Sp3;
};

class IS_PFL extends struct // Player FLags (help flags changed)
{
	const PACK = 'CCxCvv';
	const UNPACK = 'CSize/CType/CReqI/CPLID/vFlags/vSpare';

	public $Size = 8;		// 8
	public $Type = ISP_PFL;// ISP_PFL
	public $ReqI;		// 0
	public $PLID;			// player's unique id

	public $Flags;			// player flags (see below)
	public $Spare;
};

class IS_FIN extends struct // FINished race notification (not a final result - use IS_RES)
{
	const PACK = 'CCxCVVxCCxvv';
	const UNPACK = 'CSize/CType/CReqI/CPLID/VTTime/VBTime/CSpA/CNumStops/CConfirm/CSpB/vLapsDone/vFlags';

	public $Size = 20;		// 20
	public $Type = ISP_FIN;// ISP_FIN
	public $ReqI;		// 0
	public $PLID;			// player's unique id (0 = player left before result was sent)

	public $TTime;			// race time (ms)
	public $BTime;			// best lap (ms)

	public $SpA;
	public $NumStops;		// number of pit stops
	public $Confirm;		// confirmation flags : disqualified etc - see below
	public $SpB;

	public $LapsDone;		// laps completed
	public $Flags;			// player flags : help settings etc - see below
};

class IS_RES extends struct // RESult (qualify or confirmed finish)
{
	const PACK = 'CCxCa24a24a8a4VVxCCxvvCCv';
	const UNPACK = 'CSize/CType/CReqI/CPLID/a24UName/a24PName/a8Plate/a4CName/VTTime/VBTime/CSpA/CNumStops/CConfirm/CSpB/vLapsDone/vFlags/CResultNum/CNumRes/vPSeconds';

	public $Size = 84;		// 84
	public $Type = ISP_RES;// ISP_RES
	public $ReqI;			// 0 unless this is a reply to a TINY_RES request
	public $PLID;			// player's unique id (0 = player left before result was sent)

	public $UName;		// username
	public $PName;		// nickname
	public $Plate;		// number plate - NO ZERO AT END!
	public $CName;		// skin prefix

	public $TTime;			// race time (ms)
	public $BTime;			// best lap (ms)

	public $SpA;
	public $NumStops;		// number of pit stops
	public $Confirm;		// confirmation flags : disqualified etc - see below
	public $SpB;

	public $LapsDone;		// laps completed
	public $Flags;			// player flags : help settings etc - see below

	public $ResultNum;		// finish or qualify pos (0 = win / 255 = not added to table)
	public $NumRes;			// total number of results (qualify doesn't always add a new one)
	public $PSeconds;		// penalty time in seconds (already included in race time)
};

// IS_REO : REOrder - this packet can be sent in either direction

// LFS sends one at the start of every race or qualifying session, listing the start order

// You can send one to LFS before a race start, to specify the starting order.
// It may be a good idea to avoid conflict by using /start=fixed (LFS setting).
// Alternatively, you can leave the LFS setting, but make sure you send your IS_REO
// AFTER you receive the IS_VTA.  LFS does its default grid reordering at the same time
// as it sends the IS_VTA (VoTe Action) and you can override this by sending an IS_REO.

class IS_REO extends struct // REOrder (when race restarts after qualifying)
{
	const PACK = 'CCCCa32';
	const UNPACK = 'CSize/CType/CReqI/CNumP/a32PLID';

	public $Size = 36;		// 36
	public $Type = ISP_REO;// ISP_REO
	public $ReqI;			// 0 unless this is a reply to an TINY_REO request
	public $NumP;			// number of players in race

	public $PLID;		// all PLIDs in new order
};

// To request an IS_REO packet at any time, send this IS_TINY :

// ReqI : non-zero		(returned in the reply)
// SubT : TINY_REO		(request an IS_REO)

// Pit Lane Facts

define('PITLANE_EXIT',		0);	// 0 - left pit lane
define('PITLANE_ENTER',		1);	// 1 - entered pit lane
define('PITLANE_NO_PURPOSE',2);	// 2 - entered for no purpose
define('PITLANE_DT',		3);	// 3 - entered for drive-through
define('PITLANE_SG',		4);	// 4 - entered for stop-go
define('PITLANE_NUM',		5);
$PITLANE = array(PITLANE_EXIT => 'PITLANE_EXIT', PITLANE_ENTER => 'PITLANE_ENTER', PITLANE_NO_PURPOSE => 'PITLANE_NO_PURPOSE', PITLANE_DT => 'PITLANE_DT', PITLANE_SG => 'PITLANE_SG', PITLANE_NUM => 'PITLANE_NUM');

// Pit Work Flags

define('PSE_NOTHING',	(1 << 1));	// bit 0 (1)
define('PSE_STOP',		(1 << 2));	// bit 1 (2)
define('PSE_FR_DAM',	(1 << 3));	// bit 2 (4)
define('PSE_FR_WHL',	(1 << 4));	// etc...
define('PSE_LE_FR_DAM',	(1 << 6));
define('PSE_LE_FR_WHL',	(1 << 7));
define('PSE_RI_FR_DAM', (1 << 8));
define('PSE_RI_FR_WHL',	(1 << 9));
define('PSE_RE_DAM',	(1 << 10));
define('PSE_RE_WHL',	(1 << 11));
define('PSE_LE_RE_DAM',	(1 << 12));
define('PSE_LE_RE_WHL',	(1 << 13));
define('PSE_RI_RE_DAM',	(1 << 14));
define('PSE_RI_RE_WHL',	(1 << 15));
define('PSE_BODY_MINOR',(1 << 16));
define('PSE_BODY_MAJOR',(1 << 17));
define('PSE_SETUP',		(1 << 18));
define('PSE_REFUEL',	(1 << 19));
define('PSE_NUM',		20);
$PSE = array(PSE_NOTHING => 'PSE_NOTHING', PSE_STOP => 'PSE_STOP', PSE_FR_DAM => 'PSE_FR_DAM', PSE_FR_WHL => 'PSE_FR_WHL', PSE_LE_FR_DAM => 'PSE_LE_FR_DAM', PSE_LE_FR_WHL => 'PSE_LE_FR_WHL', PSE_RI_FR_DAM => 'PSE_RI_FR_DAM', PSE_RI_FR_WHL => 'PSE_RI_FR_WHL', PSE_RE_DAM => 'PSE_RE_DAM', PSE_RE_WHL => 'PSE_RE_WHL', PSE_LE_RE_DAM => 'PSE_LE_RE_DAM', PSE_LE_RE_WHL => 'PSE_LE_RE_WHL', PSE_RI_RE_DAM => 'PSE_RI_RE_DAM', PSE_RI_RE_WHL => 'PSE_RI_RE_WHL', PSE_BODY_MINOR => 'PSE_BODY_MINOR', PSE_BODY_MAJOR => 'PSE_BODY_MAJOR', PSE_SETUP => 'PSE_SETUP', PSE_REFUEL => 'PSE_REFUEL', PSE_NUM => 'PSE_NUM');

// View identifiers

define('VIEW_FOLLOW',	0);	// 0 - arcade
define('VIEW_HELI',		1);	// 1 - helicopter
define('VIEW_CAM',		2);	// 2 - tv camera
define('VIEW_DRIVER',	3);	// 3 - cockpit
define('VIEW_CUSTOM',	4);	// 4 - custom
define('VIEW_MAX',		5);
define('VIEW_ANOTHER',255); // viewing another car
$VIEW = array(VIEW_FOLLOW => 'VIEW_FOLLOW', VIEW_HELI => 'VIEW_HELI', VIEW_CAM => 'VIEW_CAM', VIEW_DRIVER => 'VIEW_DRIVER', VIEW_CUSTOM => 'VIEW_CUSTOM', VIEW_MAX => 'VIEW_MAX', VIEW_ANOTHER => 'VIEW_ANOTHER');

// Leave reasons

define('LEAVR_DISCO',	0);	// 0 - disconnect
define('LEAVR_TIMEOUT',	1);	// 1 - timed out
define('LEAVR_LOSTCONN',2);	// 2 - lost connection
define('LEAVR_KICKED',	3);	// 3 - kicked
define('LEAVR_BANNED',	4);	// 4 - banned
define('LEAVR_SECURITY',5);	// 5 - OOS or cheat protection
define('LEAVR_NUM',		6);
$LEAVR = array(LEAVR_DISCO => 'LEAVR_DISCO', LEAVR_TIMEOUT => 'LEAVR_TIMEOUT', LEAVR_LOSTCONN => 'LEAVR_LOSTCONN', LEAVR_KICKED => 'LEAVR_KICKED', LEAVR_BANNED => 'LEAVR_BANNED', LEAVR_SECURITY => 'LEAVR_SECURITY', LEAVR_NUM => 'LEAVR_NUM');

// Penalty values (VALID means the penalty can now be cleared)

define('PENALTY_NONE',		0);	// 0		
define('PENALTY_DT',		1);	// 1
define('PENALTY_DT_VALID',	2);	// 2
define('PENALTY_SG',		3);	// 3
define('PENALTY_SG_VALID',	4);	// 4
define('PENALTY_30',		5);	// 5
define('PENALTY_45',		6);	// 6
define('PENALTY_NUM',		7);
$PENALTY = array(PENALTY_NONE => 'PENALTY_NONE', PENALTY_DT => 'PENALTY_DT', PENALTY_DT_VALID => 'PENALTY_DT_VALID', PENALTY_SG => 'PENALTY_SG', PENALTY_SG_VALID => 'PENALTY_SG_VALID', PENALTY_30 => 'PENALTY_30', PENALTY_45 => 'PENALTY_45', PENALTY_NUM => 'PENALTY_NUM');

// Penalty reasons

define('PENR_UNKNOWN',		0);	// 0 - unknown or cleared penalty
define('PENR_ADMIN',		1);	// 1 - penalty given by admin
define('PENR_WRONG_WAY',	2);	// 2 - wrong way driving
define('PENR_FALSE_START',	3);	// 3 - starting before green light
define('PENR_SPEEDING',		4);	// 4 - speeding in pit lane
define('PENR_STOP_SHORT',	5);	// 5 - stop-go pit stop too short
define('PENR_STOP_LATE',	6);	// 6 - compulsory stop is too late
define('PENR_NUM',			7);
$PENR = array(PENR_UNKNOWN => 'PENR_UNKNOWN', PENR_ADMIN => 'PENR_ADMIN', PENR_WRONG_WAY => 'PENR_WRONG_WAY', PENR_FALSE_START => 'PENR_FALSE_START', PENR_SPEEDING => 'PENR_SPEEDING', PENR_STOP_SHORT => 'PENR_STOP_SHORT', PENR_STOP_LATE => 'PENR_STOP_LATE', PENR_NUM => 'PENR_NUM');

// Player flags

define('PIF_SWAPSIDE',		1);
define('PIF_RESERVED_2',	2);
define('PIF_RESERVED_4',	4);
define('PIF_AUTOGEARS',		8);
define('PIF_SHIFTER',		16);
define('PIF_RESERVED_32',	32);
define('PIF_HELP_B',		64);
define('PIF_AXIS_CLUTCH',	128);
define('PIF_INPITS',		256);
define('PIF_AUTOCLUTCH',	512);
define('PIF_MOUSE',			1024);
define('PIF_KB_NO_HELP',	2048);
define('PIF_KB_STABILISED',	4096);
define('PIF_CUSTOM_VIEW',	8192);
$PIF = array(PIF_SWAPSIDE => 'PIF_SWAPSIDE', PIF_RESERVED_2 => 'PIF_RESERVED_2', PIF_RESERVED_4 => 'PIF_RESERVED_4', PIF_AUTOGEARS => 'PIF_AUTOGEARS', PIF_SHIFTER => 'PIF_SHIFTER', PIF_RESERVED_32 => 'PIF_RESERVED_32', PIF_HELP_B => 'PIF_HELP_B', PIF_AXIS_CLUTCH => 'PIF_AXIS_CLUTCH', PIF_INPITS => 'PIF_INPITS', PIF_AUTOCLUTCH => 'PIF_AUTOCLUTCH', PIF_MOUSE => 'PIF_MOUSE', PIF_KB_NO_HELP => 'PIF_KB_NO_HELP', PIF_KB_STABILISED => 'PIF_KB_STABILISED', PIF_CUSTOM_VIEW => 'PIF_CUSTOM_VIEW');

// Tyre compounds (4 byte order : rear L, rear R, front L, front R)

define('TYRE_R1',			0);	// 0
define('TYRE_R2',			1);	// 1
define('TYRE_R3',			2);	// 2
define('TYRE_R4',			3);	// 3
define('TYRE_ROAD_SUPER',	4);	// 4
define('TYRE_ROAD_NORMAL',	5);	// 5
define('TYRE_HYBRID',		6);	// 6
define('TYRE_KNOBBLY',		7);	// 7
define('TYRE_NUM',			8);
define('NOT_CHANGED',		255);
$TYRE = array(TYRE_R1 => 'TYRE_R1', TYRE_R2 => 'TYRE_R2', TYRE_R3 => 'TYRE_R3', TYRE_R4 => 'TYRE_R4', TYRE_ROAD_SUPER => 'TYRE_ROAD_SUPER', TYRE_ROAD_NORMAL => 'TYRE_ROAD_NORMAL', TYRE_HYBRID => 'TYRE_HYBRID', TYRE_KNOBBLY => 'TYRE_KNOBBLY', TYRE_NUM => 'TYRE_NUM', NOT_CHANGED => 'NOT_CHANGED');

// Confirmation flags

define('CONF_MENTIONED',	1);
define('CONF_CONFIRMED',	2);
define('CONF_PENALTY_DT',	4);
define('CONF_PENALTY_SG',	8);
define('CONF_PENALTY_30',	16);
define('CONF_PENALTY_45',	32);
define('CONF_DID_NOT_PIT',	64);
define('CONF_DISQ',	CONF_PENALTY_DT | CONF_PENALTY_SG | CONF_DID_NOT_PIT);
define('CONF_TIME',	CONF_PENALTY_30 | CONF_PENALTY_45);
$CONF = array(CONF_MENTIONED => 'CONF_MENTIONED', CONF_CONFIRMED => 'CONF_CONFIRMED', CONF_PENALTY_DT => 'CONF_PENALTY_DT', CONF_PENALTY_SG => 'CONF_PENALTY_SG', CONF_PENALTY_30 => 'CONF_PENALTY_30', CONF_PENALTY_45 => 'CONF_PENALTY_45', CONF_DID_NOT_PIT => 'CONF_DID_NOT_PIT', CONF_DISQ => 'CONF_DISQ', CONF_TIME => 'CONF_TIME');

// Race flags

define('HOSTF_CAN_VOTE',	1);
define('HOSTF_CAN_SELECT',	2);
define('HOSTF_MID_RACE',	32);
define('HOSTF_MUST_PIT',	64);
define('HOSTF_CAN_RESET',	128);
define('HOSTF_FCV',			256);
define('HOSTF_CRUISE',		512);
$HOSTF = array(HOSTF_CAN_VOTE => 'HOSTF_CAN_VOTE', HOSTF_CAN_SELECT => 'HOSTF_CAN_SELECT', HOSTF_MID_RACE => 'HOSTF_MID_RACE', HOSTF_MUST_PIT => 'HOSTF_MUST_PIT', HOSTF_CAN_RESET => 'HOSTF_CAN_RESET', HOSTF_FCV => 'HOSTF_FCV', HOSTF_CRUISE => 'HOSTF_CRUISE');

// Passengers byte

// bit 0 female
// bit 1 front
// bit 2 female
// bit 3 rear left
// bit 4 female
// bit 5 rear middle
// bit 6 female
// bit 7 rear right


// TRACKING PACKET REQUESTS
// ========================

// To request players, connections, results or a single NLP or MCI, send an IS_TINY

// In each case, ReqI must be non-zero, and will be returned in the reply packet

// SubT : TINT_NCN - request all connections
// SubT : TINY_NPL - request all players
// SubT : TINY_RES - request all results
// SubT : TINY_NLP - request a single IS_NLP
// SubT : TINY_MCI - request a set of IS_MCI


// AUTOCROSS
// =========

// When all objects are cleared from a layout, LFS sends this IS_TINY :

// ReqI : 0
// SubT : TINY_AXC		(AutoX Cleared)

// You can request information about the current layout with this IS_TINY :

// ReqI : non-zero		(returned in the reply)
// SubT : TINY_AXI		(AutoX Info)

// The information will be sent back in this packet (also sent when a layout is loaded) :

class IS_AXI extends struct  // AutoX Info
{
	const PACK = 'CCCxCCva32';
	const UNPACK = 'CSize/CType/CReqI/CZero/CAXStart/CNumCP/vNumO/a32LName';

	public $Size = 40;		// 40
	public $Type = ISP_AXI;// ISP_AXI
	public $ReqI;			// 0 unless this is a reply to an TINY_AXI request
	public $Zero;

	public $AXStart;		// autocross start position
	public $NumCP;			// number of checkpoints
	public $NumO;			// number of objects

	public $LName;		// the name of the layout last loaded (if loaded locally)
};

// On false start or wrong route / restricted area, an IS_PEN packet is sent :

// False start : OldPen = 0 / NewPen = PENALTY_30 / Reason = PENR_FALSE_START
// Wrong route : OldPen = 0 / NewPen = PENALTY_45 / Reason = PENR_WRONG_WAY

// If an autocross object is hit (2 second time penalty) this packet is sent :

class IS_AXO extends struct // AutoX Object
{
	const PACK = 'CCxC';
	const UNPACK = 'CSize/CType/CReqI/CPLID';

	public $Size = 4;		// 4
	public $Type = ISP_AXO;// ISP_AXO
	public $ReqI;		// 0
	public $PLID;			// player's unique id
};


// CAR TRACKING - car position info sent at constant intervals
// ============

// IS_NLP - compact, all cars in 1 variable sized packet
// IS_MCI - detailed, max 8 cars per variable sized packet

// To receive IS_NLP or IS_MCI packets at a specified interval :

// 1) Set the Interval field in the IS_ISI (InSimInit) packet (50, 60, 70... 8000 ms)
// 2) Set one of the flags ISF_NLP or ISF_MCI in the IS_ISI packet

// If ISF_NLP flag is set, one IS_NLP packet is sent...

class NodeLap // Car info in 6 bytes - there is an array of these in the NLP (below)
{
	const PACK = 'vvCC';
	const UNPACK = 'vNode/vLap/CPLID/CPosition';

	public $Node;		// current path node
	public $Lap;		// current lap
	public $PLID;		// player's unique id
	public $Position;	// current race position : 0 = unknown, 1 = leader, etc...
};

class IS_NLP extends struct // Node and Lap Packet - variable size
{
	const PACK = 'CCCC';
	const UNPACK = 'CSize/CType/CReqI/CNumP';

	public $Size;			// 4 + NumP * 6 (PLUS 2 if needed to make it a multiple of 4)
	public $Type = ISP_NLP;// ISP_NLP
	public $ReqI;			// 0 unless this is a reply to an TINY_NLP request
	public $NumP;			// number of players in race

	public $Info;		// node and lap of each player, 1 to 32 of these (NumP)
};

// If ISF_MCI flag is set, a set of IS_MCI packets is sent...

class CompCar // Car info in 28 bytes - there is an array of these in the MCI (below)
{
	const PACK = 'vvCCCxlllvvvs';
	const UNPACK = 'vNode/vLap/CPLID/CPosition/CInfo/CSp3/lX/lY/lZ/vSpeed/vDirection/vHeading/sAngVel';

	public $Node;		// current path node
	public $Lap;		// current lap
	public $PLID;		// player's unique id
	public $Position;	// current race position : 0 = unknown, 1 = leader, etc...
	public $Info;		// flags and other info - see below
	public $Sp3;
	public $X;			// X map (65536 = 1 metre)
	public $Y;			// Y map (65536 = 1 metre)
	public $Z;			// Z alt (65536 = 1 metre)
	public $Speed;		// speed (32768 = 100 m/s)
	public $Direction;	// direction of car's motion : 0 = world y direction, 32768 = 180 deg
	public $Heading;	// direction of forward axis : 0 = world y direction, 32768 = 180 deg
	public $AngVel;		// signed, rate of change of heading : (16384 = 360 deg/s)
};

// NOTE 1) Info byte - the bits in this byte have the following meanings :

define('CCI_BLUE',		1);		// this car is in the way of a driver who is a lap ahead
define('CCI_YELLOW',	2);		// this car is slow or stopped and in a dangerous place
define('CCI_LAG',		32);	// this car is lagging (missing or delayed position packets)
define('CCI_FIRST',		64);	// this is the first compcar in this set of MCI packets
define('CCI_LAST',		128);	// this is the last compcar in this set of MCI packets
$CCI = array(CCI_BLUE => 'CCI_BLUE', CCI_YELLOW => 'CCI_YELLOW', CCI_LAG => 'CCI_LAG', CCI_FIRST => 'CCI_FIRST', CCI_LAST => 'CCI_LAST');

// NOTE 2) Heading : 0 = world y axis direction, 32768 = 180 degrees, anticlockwise from above
// NOTE 3) AngVel  : 0 = no change in heading,    8192 = 180 degrees per second anticlockwise

class IS_MCI extends struct // Multi Car Info - if more than 8 in race then more than one of these is sent
{
	const PACK = 'CCCC';
	const UNPACK = 'CSize/CType/CReqI/CNumC';

	public $Size;			// 4 + NumC * 28
	public $Type = ISP_MCI;// ISP_MCI
	public $ReqI;			// 0 unless this is a reply to an TINY_MCI request
	public $NumC;			// number of valid CompCar structs in this packet

	public $Info;		// car info for each player, 1 to 8 of these (NumC)
};

// You can change the rate of NLP or MCI after initialisation by sending this IS_SMALL :

// ReqI : 0
// SubT : SMALL_NLI		(Node Lap Interval)
// UVal : interval      (0 means stop, otherwise time interval : 50, 60, 70... 8000 ms)


// CAR POSITION PACKETS (Initialising OutSim from InSim - See "OutSim" below)
// ====================

// To request Car Positions from the currently viewed car, send this IS_SMALL :

// ReqI : 0
// SubT : SMALL_SSP		(Start Sending Positions)
// UVal : interval		(time between updates - zero means stop sending)

// If OutSim has not been setup in cfg.txt, the SSP packet makes LFS send UDP packets
// if in game, using the OutSim system as documented near the end of this text file.

// You do not need to set any OutSim values in LFS cfg.txt - OutSim is fully
// initialised by the SSP packet.

// The OutSim packets will be sent to the UDP port specified in the InSimInit packet.

// NOTE : OutSim packets are not InSim packets and don't have a 4-byte header.


// DASHBOARD PACKETS (Initialising OutGauge from InSim - See "OutGauge" below)
// =================

// To request Dashboard Packets from the currently viewed car, send this IS_SMALL :

// ReqI : 0
// SubT : SMALL_SSG		(Start Sending Gauges)
// UVal : interval		(time between updates - zero means stop sending)

// If OutGauge has not been setup in cfg.txt, the SSG packet makes LFS send UDP packets
// if in game, using the OutGauge system as documented near the end of this text file.

// You do not need to set any OutGauge values in LFS cfg.txt - OutGauge is fully
// initialised by the SSG packet.

// The OutGauge packets will be sent to the UDP port specified in the InSimInit packet.

// NOTE : OutGauge packets are not InSim packets and don't have a 4-byte header.


// CAMERA CONTROL
// ==============

// IN GAME camera control
// ----------------------

// You can set the viewed car and selected camera directly with a special packet
// These are the states normally set in game by using the TAB and V keys

class IS_SCC extends struct // Set Car Camera - Simplified camera packet (not SHIFT+U mode)
{
	const PACK = 'CCxxCCxx';
	const UNPACK = 'CSize/CType/CReqI/CZero/CViewPLID/CInGameCam/CSp2/CSp3';

	public $Size = 8;		// 8
	public $Type = ISP_SCC;// ISP_SCC
	public $ReqI;		// 0
	public $Zero;

	public $ViewPLID;	// UniqueID of player to view
	public $InGameCam;	// InGameCam (as reported in StatePack)
	public $Sp2;
	public $Sp3;
};

// NOTE : Set InGameCam or ViewPLID to 255 to leave that option unchanged.

// DIRECT camera control
// ---------------------

// A Camera Position Packet can be used for LFS to report a camera position and state.
// An InSim program can also send one to set LFS camera position in game or SHIFT+U mode.

// Type : "Vec" : 3 ints (X, Y, Z) - 65536 means 1 metre

class IS_SPP extends struct // Cam Pos Pack - Full camera packet (in car OR SHIFT+U mode)
{
	const PACK = 'CCCxl3vvvCCfvv';
	const UNPACL = 'CSize/CType/CReqI/CZero/l3Pos/vH/vP/vR/CViewPLID/CInGameCam/fFOV/CTime/CFlags';

	public $Size = 32;		// 32
	public $Type = ISP_CPP;// ISP_CPP
	public $ReqI;			// instruction : 0 / or reply : ReqI as received in the TINY_SCP
	public $Zero;

	public $Pos;			// Position vector

	public $H;				// heading - 0 points along Y axis
	public $P;				// pitch   - 0 means looking at horizon
	public $R;				// roll    - 0 means no roll

	public $ViewPLID;		// Unique ID of viewed player (0 = none)
	public $InGameCam;		// InGameCam (as reported in StatePack)

	public $FOV;			// 4-byte float : FOV in degrees

	public $Time;			// Time to get there (0 means instant + reset)
	public $Flags;			// ISS state flags (see below)
};

// The ISS state flags that can be set are :

// ISS_SHIFTU			- in SHIFT+U mode
// ISS_SHIFTU_HIGH		- HIGH view
// ISS_SHIFTU_FOLLOW	- following car
// ISS_VIEW_OVERRIDE	- override user view

// On receiving this packet, LFS will set up the camera to match the values in the packet,
// including switching into or out of SHIFT+U mode depending on the ISS_SHIFTU flag.

// If ISS_SHIFTU is not set, then ViewPLID and InGameCam will be used.

// If ISS_VIEW_OVERRIDE is set, the in-car view Heading Pitch and Roll will be taken
// from the values in this packet.  Otherwise normal in game control will be used.

// Position vector (Vec Pos) - in SHIFT+U mode, Pos can be either relative or absolute.

// If ISS_SHIFTU_FOLLOW is set, it's a following camera, so the position is relative to
// the selected car.  Otherwise, the position is absolute, as used in normal SHIFT+U mode.

// NOTE : Set InGameCam or ViewPLID to 255 to leave that option unchanged.

// SMOOTH CAMERA POSITIONING
// --------------------------

// The "Time" value in the packet is used for camera smoothing.  A zero Time means instant
// positioning.  Any other value (milliseconds) will cause the camera to move smoothly to
// the requested position in that time.  This is most useful in SHIFT+U camera modes or
// for smooth changes of internal view when using the ISS_VIEW_OVERRIDE flag.

// NOTE : You can use frequently updated camera positions with a longer Time value than
// the update frequency.  For example, sending a camera position every 100 ms, with a
// Time value of 1000 ms.  LFS will make a smooth motion from the rough inputs.

// If the requested camera mode is different from the one LFS is already in, it cannot
// move smoothly to the new position, so in this case the "Time" value is ignored.

// GETTING A CAMERA PACKET
// -----------------------

// To GET a CamPosPack from LFS, send this IS_TINY :

// ReqI : non-zero		(returned in the reply)
// SubT : TINY_SCP		(Send Cam Pos)

// LFS will reply with a CamPosPack as described above.  You can store this packet
// and later send back exactly the same packet to LFS and it will try to replicate
// that camera position.


// TIME CONTROL
// ============

// Request the current time at any point with this IS_TINY :

// ReqI : non-zero		(returned in the reply)
// SubT : TINY_GTH		(Get Time in Hundredths)

// The time will be sent back in this IS_SMALL :

// ReqI : non-zero		(as received in the request packet)
// SubT : SMALL_RTP		(Race Time Packet)
// UVal	: Time			(hundredths of a second since start of race or replay)

// You can stop or start time in LFS and while it is stopped you can send packets to move
// time in steps.  Time steps are specified in hundredths of a second.
// Warning : unlike pausing, this is a "trick" to LFS and the program is unaware of time
// passing so you must not leave it stopped because LFS is unusable in that state.
// This packet is not available in live multiplayer mode.

// Stop and Start with this IS_SMALL :

// ReqI : 0
// SubT : SMALL_TMS		(TiMe Stop)
// UVal	: stop			(1 - stop / 0 - carry on)

// When STOPPED, make time step updates with this IS_SMALL :

// ReqI : 0
// SubT : SMALL_STP		(STeP)
// UVal : number		(number of hundredths of a second to update)


// REPLAY CONTROL
// ==============

// You can load a replay or set the position in a replay with an IS_RIP packet.
// Replay positions and lengths are specified in hundredths of a second.
// LFS will reply with another IS_RIP packet when the request is completed.

class IS_RIP extends struct // Replay Information Packet
{
	const PACK = 'CCCCCCCxVVa64';
	const UNPACK = 'CSize/CType/CReqI/CError/CMPR/CPaused/COptions/CSp3/VCTime/VTTime/a64RName';

	public $Size = 80;		// 80
	public $Type = ISP_RIP;// ISP_RIP
	public $ReqI;			// request : non-zero / reply : same value returned
	public $Error;			// 0 or 1 = OK / other values are listed below

	public $MPR;			// 0 = SPR / 1 = MPR
	public $Paused;			// request : pause on arrival / reply : paused state
	public $Options;		// various options - see below
	public $Sp3;

	public $CTime;			// (hundredths) request : destination / reply : position
	public $TTime;			// (hundredths) request : zero / reply : replay length

	public $RName;		// zero or replay name - last byte must be zero
};

// NOTE about RName :
// In a request, replay RName will be loaded.  If zero then the current replay is used.
// In a reply, RName is the name of the current replay, or zero if no replay is loaded.

// You can request an IS_RIP packet at any time with this IS_TINY :

// ReqI : non-zero		(returned in the reply)
// SubT : TINY_RIP		(Replay Information Packet)

// Error codes returned in IS_RIP replies :

define('RIP_OK',			0);	//  0 - OK : completed instruction
define('RIP_ALREADY',		1);	//  1 - OK : already at the destination
define('RIP_DEDICATED',		2);	//  2 - can't run a replay - dedicated host
define('RIP_WRONG_MODE',	3);	//  3 - can't start a replay - not in a suitable mode
define('RIP_NOT_REPLAY',	4);	//  4 - RName is zero but no replay is currently loaded
define('RIP_CORRUPTED',		5);	//  5 - IS_RIP corrupted (e.g. RName does not end with zero)
define('RIP_NOT_FOUND',		6);	//  6 - the replay file was not found
define('RIP_UNLOADABLE',	7);	//  7 - obsolete / future / corrupted
define('RIP_DEST_OOB',		8);	//  8 - destination is beyond replay length
define('RIP_UNKNOWN',		9);	//  9 - unknown error found starting replay
define('RIP_USER',			10);// 10 - replay search was terminated by user
define('RIP_OOS',			10);// 11 - can't reach destination - SPR is out of sync
$RIP = array(RIP_OK => 'RIP_OK', RIP_ALREADY => 'RIP_ALREADY', RIP_DEDICATED => 'RIP_DEDICATED', RIP_WRONG_MODE => 'RIP_WRONG_MODE', RIP_NOT_REPLAY => 'RIP_NOT_REPLAY', RIP_CORRUPTED => 'RIP_CORRUPTED', RIP_NOT_FOUND => 'RIP_NOT_FOUND', RIP_UNLOADABLE => 'RIP_UNLOADABLE', RIP_DEST_OOB => 'RIP_DEST_OOB', RIP_UNKNOWN => 'RIP_UNKNOWN', RIP_USER => 'RIP_USER', RIP_OOS => 'RIP_OOS');

// Options byte : some options

define('RIPOPT_LOOP',	1);	// replay will loop if this bit is set
define('RIPOPT_SKINS',	2);	// set this bit to download missing skins
$RIPOPT = array(RIPOPT_LOOP => 'RIPOPT_LOOP', RIPOPT_SKINS => 'RIPOPT_SKINS');

// SCREENSHOTS
// ===========

// You can instuct LFS to save a screenshot using the IS_SSH packet.
// The screenshot will be saved as an uncompressed BMP in the data\shots folder.
// BMP can be a filename (excluding .bmp) or zero - LFS will create a file name.
// LFS will reply with another IS_SSH when the request is completed.

class IS_SSH extends struct // ScreenSHot
{
	const PACK = 'CCCCxxxxa32';
	const UNPACK = 'CSize/CType/CReqI/CError/CSp0/CSp1/CSp2/CSp3/a32BMP';

	public $Size = 40;		// 40
	public $Type = ISP_SSH;// ISP_SSH
	public $ReqI;			// request : non-zero / reply : same value returned
	public $Error;			// 0 = OK / other values are listed below

	public $Sp0;			// 0
	public $Sp1;			// 0
	public $Sp2;			// 0
	public $Sp3;			// 0

	public $BMP;	// name of screenshot file - last byte must be zero
};

// Error codes returned in IS_SSH replies :

define('SSH_OK',		0);	//  0 - OK : completed instruction
define('SSH_DEDICATED',	1);	//  1 - can't save a screenshot - dedicated host
define('SSH_CORRUPTED',	2);	//  2 - IS_SSH corrupted (e.g. BMP does not end with zero)
define('SSH_NO_SAVE',	3);	//  3 - could not save the screenshot
$SSH = array(SSH_OK => 'SSH_OK', SSH_DEDICATED => 'SSH_DEDICATED', SSH_CORRUPTED => 'SSH_CORRUPTED', SSH_NO_SAVE => 'SSH_NO_SAVE');

// BUTTONS
// =======

// You can make up to 240 buttons appear on the host or guests (ID = 0 to 239).
// You should set the ISF_LOCAL flag (in IS_ISI) if your program is not a host control
// system, to make sure your buttons do not conflict with any buttons sent by the host.

// LFS can display normal buttons in these four screens :

// - main entry screen
// - game setup screen
// - in game
// - SHIFT+U mode

// The recommended area for most buttons is defined by :

define('IS_X_MIN',	0);
define('IS_X_MAX',	110);
define('IS_Y_MIN',	30);
define('IS_Y_MAX',	170);
$IS = array(IS_X_MIN => 'IS_X_MIN', IS_X_MAX => 'IS_X_MAX', IS_Y_MIN => 'IS_Y_MIN', IS_Y_MAX => 'IS_Y_MAX');

// If you draw buttons in this area, the area will be kept clear to
// avoid overlapping LFS buttons with your InSim program's buttons.
// Buttons outside that area will not have a space kept clear.
// You can also make buttons visible in all screens - see below.

// To delete one button or clear all buttons, send this packet :

class IS_BFN extends struct  // Button FunctioN - delete buttons / receive button requests
{
	const PACK = 'CCxCCCCx';
	const UNPACK = 'CSize/CType/CReqI/CSubT/CUCID/CClickID/CInst/CSp3';

	public $Size = 8;		// 8
	public $Type = ISP_BFN;// ISP_BFN
	public $ReqI;		// 0
	public $SubT;			// subtype, from BFN_ enumeration (see below)

	public $UCID;		// connection to send to or from (0 = local / 255 = all)
	public $ClickID;	// ID of button to delete (if SubT is BFN_DEL_BTN)
	public $Inst;		// used internally by InSim
	public $Sp3;
};

// the fourth byte of IS_BFN packets is one of these
define('BFN_DEL_BTN',	0);	//  0 - instruction     : delete one button (must set ClickID)
define('BFN_CLEAR',		1);	//  1 - instruction		: clear all buttons made by this insim instance
define('BFN_USER_CLEAR',2);	//  2 - info            : user cleared this insim instance's buttons
define('BFN_REQUEST',	3);	//  3 - user request    : SHIFT+B or SHIFT+I - request for buttons
$BFN = array(BFN_DEL_BTN => 'BFN_DEL_BTN', BFN_CLEAR => 'BFN_CLEAR', BFN_USER_CLEAR => 'BFN_USER_CLEAR', BFN_REQUEST => 'BFN_REQUEST');

// NOTE : BFN_REQUEST allows the user to bring up buttons with SHIFT+B or SHIFT+I

// SHIFT+I clears all host buttons if any - or sends a BFN_REQUEST to host instances
// SHIFT+B is the same but for local buttons and local instances

// To send a button to LFS, send this variable sized packet

class IS_BTN extends struct // BuTtoN - button header - followed by 0 to 240 characters
{
	const PACK = 'CCCCCCCCCCCCa240';
	const UNPACK = 'CSize/CType/CReqI/CUCID/CClickID/CInst/CBStyle/CTypeIn/CL/CT/CW/CH/a240Text';

	public $Size;			// 12 + TEXT_SIZE (a multiple of 4)
	public $Type = ISP_BTN;// ISP_BTN
	public $ReqI;		// non-zero (returned in IS_BTC and IS_BTT packets)
	public $UCID;		// connection to display the button (0 = local / 255 = all)

	public $ClickID;	// button ID (0 to 239)
	public $Inst;		// some extra flags - see below
	public $BStyle;		// button style flags - see below
	public $TypeIn;		// max chars to type in - see below

	public $L;			// left   : 0 - 200
	public $T;			// top    : 0 - 200
	public $W;			// width  : 0 - 200
	public $H;			// height : 0 - 200

	public $Text;		// 0 to 240 characters of text

	function pack()
	{
		PRISM::CONSOLE('Needs to be implamented');
	}

};

// ClickID byte : this value is returned in IS_BTC and IS_BTT packets.

// Host buttons and local buttons are stored separately, so there is no chance of a conflict between
// a host control system and a local system (although the buttons could overlap on screen).

// Programmers of local InSim programs may wish to consider using a configurable button range and
// possibly screen position, in case their users will use more than one local InSim program at once.

// TypeIn byte : if set, the user can click this button to type in text.

// Lowest 7 bits are the maximum number of characters to type in (0 to 95)
// Highest bit (128) can be set to initialise dialog with the button's text

// On clicking the button, a text entry dialog will be opened, allowing the specified number of
// characters to be typed in.  The caption on the text entry dialog is optionally customisable using
// Text in the IS_BTN packet.  If the first character of IS_BTN's Text field is zero, LFS will read
// the caption up to the second zero.  The visible button text then follows that second zero.

// Text : 0-65-66-0 would display button text "AB" and no caption

// Text : 0-65-66-67-0-68-69-70-71-0-0-0 would display button text "DEFG" and caption "ABC"

// Inst byte : mainly used internally by InSim but also provides some extra user flags

define('INST_ALWAYS_ON',	128);// if this bit is set the button is visible in all screens
$INST = array(INST_ALWAYS_ON => 'INST_ALWAYS_ON');

// NOTE : You should not use INST_ALWAYS_ON for most buttons.  This is a special flag for buttons
// that really must be on in all screens (including the garage and options screens).  You will
// probably need to confine these buttons to the top or bottom edge of the screen, to avoid
// overwriting LFS buttons.  Most buttons should be defined without this flag, and positioned
// in the recommended area so LFS can keep a space clear in the main screens.

// BStyle byte : style flags for the button

define('ISB_C1',		1);		// you can choose a standard
define('ISB_C2',		2);		// interface colour using
define('ISB_C4',		4);		// these 3 lowest bits - see below
define('ISB_CLICK',		8);		// click this button to send IS_BTC
define('ISB_LIGHT',		16);	// light button
define('ISB_DARK',		32);	// dark button
define('ISB_LEFT',		64);	// align text to left
define('ISB_RIGHT',		128);	// align text to right

// colour 0 : light grey		(not user editable)
// colour 1 : title colour		(default:yellow)
// colour 2 : unselected text	(default:black)
// colour 3 : selected text		(default:white)
// colour 4 : ok				(default:green)
// colour 5 : cancel			(default:red)
// colour 6 : text string		(default:pale blue)
// colour 7 : unavailable		(default:grey)

// NOTE : If width or height are zero, this would normally be an invalid button.  But in that case if
// there is an existing button with the same ClickID, all the packet contents are ignored except the
// Text field.  This can be useful for updating the text in a button without knowing its position.
// For example, you might reply to an IS_BTT using an IS_BTN with zero W and H to update the text.

// Replies : If the user clicks on a clickable button, this packet will be sent :

class IS_BTC extends struct // BuTton Click - sent back when user clicks a button
{
	const PACK = 'CCCCCCCx';
	const UNPACK = 'CSize/CType/CReqI/CUCID/CClickID/CInst/CCFlags/CSp3';

	public $Size = 8;		// 8
	public $Type = ISP_BTC;// ISP_BTC
	public $ReqI;			// ReqI as received in the IS_BTN
	public $UCID;			// connection that clicked the button (zero if local)

	public $ClickID;		// button identifier originally sent in IS_BTN
	public $Inst;			// used internally by InSim
	public $CFlags;			// button click flags - see below
	public $Sp3;
};

// CFlags byte : click flags

define('ISB_LMB',		1);		// left click
define('ISB_RMB',		2);		// right click
define('ISB_CTRL',		4);		// ctrl + click
define('ISB_SHIFT',		8);		// shift + click
$ISB = array(ISB_LMB => 'ISB_LMB', ISB_RMB => 'ISB_RMB', ISB_CTRL => 'ISB_CTRL', ISB_SHIFT => 'ISB_SHIFT');

// If the TypeIn byte is set in IS_BTN the user can type text into the button
// In that case no IS_BTC is sent - an IS_BTT is sent when the user presses ENTER

class IS_BTT extends struct // BuTton Type - sent back when user types into a text entry button
{
	const PACK = 'CCCCCCCxa96';
	const UNPACK = 'CSize/CType/CReqI/CUCID/CClickID/CInst/CTypeIn/CSp3/a96Text';

	public $Size = 104;	// 104
	public $Type = ISP_BIT;// ISP_BTT
	public $ReqI;			// ReqI as received in the IS_BTN
	public $UCID;			// connection that typed into the button (zero if local)

	public $ClickID;		// button identifier originally sent in IS_BTN
	public $Inst;			// used internally by InSim
	public $TypeIn;			// from original button specification
	public $Sp3;

	public $Text;		// typed text, zero to TypeIn specified in IS_BTN
};


// OutSim - MOTION SIMULATOR SUPPORT
// ======

// The user's car in multiplayer or the viewed car in single player or
// single player replay can output information to a motion system while
// viewed from an internal view.

// This can be controlled by 5 lines in the cfg.txt file :

// OutSim Mode 0        :0-off 1-driving 2-driving+replay
// OutSim Delay 1       :minimum delay between packets (100ths of a sec)
// OutSim IP 0.0.0.0    :IP address to send the UDP packet
// OutSim Port 0        :IP port
// OutSim ID 0          :if not zero, adds an identifier to the packet

// Each update sends the following UDP packet :

class OutSimPack extends struct
{
	const PACK = 'Vf3ffff3f3fll';
	const UNPACK = 'VTime/f3AngVel/fHeading/fPitch/fRoll/f3Accel/f3Vel/l3Pos/lID';

	public $Time;	// time in milliseconds (to check order)

	public $AngVel;	// 3 floats, angular velocity vector
	public $Heading;// anticlockwise from above (Z)
	public $Pitch;	// anticlockwise from right (X)
	public $Roll;	// anticlockwise from front (Y)
	public $Accel;	// 3 floats X, Y, Z
	public $Vel;	// 3 floats X, Y, Z
	public $Pos;	// 3 ints   X, Y, Z (1m = 65536)

	public $ID;		// optional - only if OutSim ID is specified
};

// NOTE 1) X and Y axes are on the ground, Z is up.

// NOTE 2) Motion simulators can be dangerous.  The Live for Speed developers do
// not support any motion systems in particular and cannot accept responsibility
// for injuries or deaths connected with the use of such machinery.


// OutGauge - EXTERNAL DASHBOARD SUPPORT
// ========

// The user's car in multiplayer or the viewed car in single player or
// single player replay can output information to a dashboard system
// while viewed from an internal view.

// This can be controlled by 5 lines in the cfg.txt file :

// OutGauge Mode 0        :0-off 1-driving 2-driving+replay
// OutGauge Delay 1       :minimum delay between packets (100ths of a sec)
// OutGauge IP 0.0.0.0    :IP address to send the UDP packet
// OutGauge Port 0        :IP port
// OutGauge ID 0          :if not zero, adds an identifier to the packet

// Each update sends the following UDP packet :

class OutGaugePack extends struct
{
	const PACK = 'Va4vCxfffffffVVfffa16a16l';
	const UNPACK = 'VTime/a4Car/vFlags/CGear/CSpareB/fSpeed/fRPM/fTurbo/fEngTemp/fFuel/fOilPressure/fOilTemp/VDashLights/VShowLights/fThrottle/fBrake/fClutch/a16Display1/a16Display2/lID';

	public $Time;			// time in milliseconds (to check order)

	public $Car;			// Car name
	public $Flags;			// Info (see OG_x below)
	public $Gear;			// Reverse:0, Neutral:1, First:2...
	public $SpareB;
	public $Speed;			// M/S
	public $RPM;			// RPM
	public $Turbo;			// BAR
	public $EngTemp;		// C
	public $Fuel;			// 0 to 1
	public $OilPressure;	// BAR
	public $OilTemp;		// C
	public $DashLights;		// Dash lights available (see DL_x below)
	public $ShowLights;		// Dash lights currently switched on
	public $Throttle;		// 0 to 1
	public $Brake;			// 0 to 1
	public $Clutch;			// 0 to 1
	public $Display1;	// Usually Fuel
	public $Display2;	// Usually Settings

	public $ID;				// optional - only if OutGauge ID is specified
};

// OG_x - bits for OutGaugePack Flags

define('OG_TURBO',	8192);	// show turbo gauge
define('OG_KM',		16384);	// if not set - user prefers MILES
define('OG_BAR',	32768);	// if not set - user prefers PSI
$OG = array(OG_TURBO => 'OG_TURBO', OG_KM => 'OG_KM', OG_BAR => 'OG_BAR');

// DL_x - bits for OutGaugePack DashLights and ShowLights

define('DL_SHIFT',		(1 << 1));	// bit 0	- shift light
define('DL_FULLBEAM',	(1 << 2));	// bit 1	- full beam
define('DL_HANDBRAKE',	(1 << 3));	// bit 2	- handbrake
define('DL_PITSPEED',	(1 << 4));	// bit 3	- pit speed limiter
define('DL_TC',			(1 << 5));	// bit 4	- TC active or switched off
define('DL_SIGNAL_L',	(1 << 6));	// bit 5	- left turn signal
define('DL_SIGNAL_R',	(1 << 7));	// bit 6	- right turn signal
define('DL_SIGNAL_ANY',	(1 << 8));	// bit 7	- shared turn signal
define('DL_OILWARN',	(1 << 9));	// bit 8	- oil pressure warning
define('DL_BATTERY',	(1 << 10));	// bit 9	- battery warning
define('DL_ABS',		(1 << 11));	// bit 10	- ABS active or switched off
define('DL_SPARE',		(1 << 12));	// bit 11
define('DL_NUM',		13);
$DL = array(DL_SHIFT => 'DL_SHIFT', DL_FULLBEAM => 'DL_FULLBEAM', DL_HANDBRAKE => 'DL_HANDBRAKE', DL_PITSPEED => 'DL_PITSPEED', DL_TC => 'DL_TC', DL_SIGNAL_L => 'DL_SIGNAL_L', DL_SIGNAL_R => 'DL_SIGNAL_R', DL_SIGNAL_ANY => 'DL_SIGNAL_ANY', DL_OILWARN => 'DL_OILWARN', DL_BATTERY => 'DL_BATTERY', DL_ABS => 'DL_ABS', DL_SPARE => 'DL_SPARE', DL_NUM => 'DL_NUM');

//////
#endif

// InSimRelay for LFS InSim version 4 (LFS 0.5X and up)
//
// The Relay code below can be seen as an extension to the regular 
// InSim protocol, as the packets are constructed in the same
// manner as regular InSim packets.
//
// Connect your client to isrelay.lfs.net:47474 with TCP
// After you are connected you can request a hostlist, so you can see
// which hosts you can connect to.
// Then you can send a packet to the Relay to select a host. After that
// the Relay will send you all insim data from that host.

// Some hosts require a spectator password in order to be selectable.

// You do not need to specify a spectator password if you use a valid administrator password.

// If you connect with an administrator password, you can send just about every
// regular InSim packet there is available in LFS, just like as if you were connected
// to the host directly. For a full list, see end of document.




// Packet types used for the Relay

define('IRP_ARQ',	250);	// Send : request if we are host admin (after connecting to a host)
define('IRP_ARP',	251);	// Receive : replies if you are admin (after connecting to a host)
define('IRP_HLR',	252);	// Send : To request a hostlist
define('IRP_HOS',	253);	// Receive : Hostlist info
define('IRP_SEL',	254);	// Send : To select a host
define('IRP_ERR',	255);	// Receive : An error number
$IRP = array(IRP_ARQ => 'IRP_ARQ', IRP_ARP => 'IRP_ARP', IRP_HLR => 'IRP_HLR', IRP_HOS => 'IRP_HOS', IRP_SEL => 'IRP_SEL', IRP_ERR => 'IRP_ERR');

// To request a hostlist from the Relay, send this packet :

class IR_HLR extends struct // HostList Request
{
	const PACK = 'CCCx';
	const UNPACK = 'CSize/CType/CReqI/CSp0';

	public $Size = 4;		// 4
	public $Type = IRP_HLR;// IRP_HLR
	public $ReqI;
	public $Sp0;
};


// That will return (multiple) packets containing hostnames and some information about them

// The following struct is a subpacket of the IR_HOS packet

class HInfo // Sub packet for IR_HOS. Contains host information
{
	const PACK = 'a32a6CC';
	const UNPACK = 'a32HName/a6Track/CFlags/CNumConns';

	public $HName;	// Name of the host

	public $Track;	// Short track name
	public $Flags;		// Info flags about the host - see NOTE 1) below
	public $NumConns;	// Number of people on the host
};

// NOTE 1)

define('HOS_SPECPASS',		1);		// Host requires a spectator password
define('HOS_LICENSED',		2);		// Bit is set if host is licensed
define('HOS_S1',			4);		// Bit is set if host is S1
define('HOS_S2',			8);		// Bit is set if host is S2
define('HOS_FIRST',			64);	// Indicates the first host in the list
define('HOS_LAST',			128);	// Indicates the last host in the list
$HOS = array(HOS_SPECPASS => 'HOS_SPECPASS', HOS_LICENSED => 'HOS_LICENSED', HOS_S1 => 'HOS_S1', HOS_S2 => 'HOS_S2', HOS_FIRST => 'HOS_FIRST', HOS_LAST => 'HOS_LAST');


class IR_HOS extends struct // Hostlist (hosts connected to the Relay)
{
	const PACK = 'CCCCa6';
	const UNPACK = 'CSize/CType/CReqI/CNumHosts/a6Info';

	public $Size;			// 4 + NumHosts * 40
	public $Type = IRP_HOS;// IRP_HOS
	public $ReqI;			// As given in IR_HLR
	public $NumHosts;		// Number of hosts described in this packet

	public $Info;		// Host info for every host in the Relay. 1 to 6 of these in a IR_HOS

	public function unpack()
	{
		PRISM::CONSOLE('Needs to be implamented');
	}
};


// To select a host in the Relay, send this packet :

class IR_SEL extends struct // Relay select - packet to select a host, so relay starts sending you data.
{
	const PACK = 'CCCxa32a16a16';
	const UNPACK = 'CSize/CType/CReqI/CZero/a32HName/a16Admin/a16Spec';

	public $Size = 68;		// 68
	public $Type = IRP_SEL;// IRP_SEL
	public $ReqI;			// If non-zero Relay will reply with an IS_VER packet
	public $Zero;		// 0

	public $HName;		// Hostname to receive data from - may be colourcode stripped
	public $Admin;		// Admin password (to gain admin access to host)
	public $Spec;		// Spectator password (if host requires it)

};


// To request if we are an admin send:

class IR_ARQ extends struct // Admin Request
{
	const PACK = 'CCCx';
	const UNPACK = 'CSize/CType/CReqI/CSp0';

	public $Size = 4;		// 4
	public $Type = IRP_ARQ;// IRP_ARQ
	public $ReqI;
	public $Sp0;
};


// Relay will reply to admin status request :

class IR_ARP extends struct // Admin Response
{
	const PACK = 'CCCC';
	const UNPACK = 'CSize/CType/CReqI/CAdmin';

	public $Size = 4;		// 4
	public $Type = IRP_ARP;// IRP_ARP
	public $ReqI;
	public $Admin;			// 0- no admin; 1- admin
};


// If you specify a wrong value, like invalid packet / hostname / adminpass / specpass, 
// the Relay returns an error packet :

class IR_ERR extends struct
{
	const PACK = 'CCCC';
	const UNPACk = 'CSize/CType/CReqI/CErrNo';

	public $Size = 4;		// 4
	public $Type = IRP_ERR;// IRP_ERR
	public $ReqI;		// As given in RL_SEL, otherwise 0
	public $ErrNo;		// Error number - see NOTE 2) below
};

// NOTE 2) Error numbers :

define('IR_ERR_PACKET',		1);	// Invalid packet sent by client (wrong structure / length)
define('IR_ERR_PACKET2',	2);	// Invalid packet sent by client (packet was not allowed to be forwarded to host)
define('IR_ERR_HOSTNAME',	3);	// Wrong hostname given by client
define('IR_ERR_ADMIN',		4);	// Wrong admin pass given by client
define('IR_ERR_SPEC',		5);	// Wrong spec pass given by client
define('IR_ERR_NOSPEC',		6);	// Spectator pass required, but none given
$IR = array(IR_ERR_PACKET => 'IR_ERR_PACKET', IR_ERR_PACKET2 => 'IR_ERR_PACKET2', IR_ERR_HOSTNAME => 'IR_ERR_HOSTNAME', IR_ERR_ADMIN => 'IR_ERR_ADMIN', IR_ERR_SPEC => 'IR_ERR_SPEC', IR_ERR_NOSPEC => 'IR_ERR_NOSPEC');

/*
==============================================
Regular insim packets that a relay client can send to host :

For anyone
TINY_VER
TINY_PING
TINY_SCP
TINY_SST
TINY_GTH
TINY_ISM
TINY_NCN
TINY_NPL
TINY_RES
TINY_REO
TINY_RST
TINY_AXI

Admin only
TINY_VTC
ISP_MST
ISP_MSX
ISP_MSL
ISP_MTC
ISP_SCH
ISP_BFN
ISP_BTN

The relay will also accept, but not forward
TINY_NONE    // for relay-connection maintenance
*/

/* Start of PRISM PACKET FOOTER */
/* Packet Handler Help */
$TYPEs = $ISP + $IRP;
foreach ($TYPEs as $Type => $Name)
{
	$TYPEs[$Type] = substr_replace($Name, '', 2, 1);
}
/* End of PRISM PACKET FOOTER */

?>