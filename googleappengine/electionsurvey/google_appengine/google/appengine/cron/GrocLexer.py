#!/usr/bin/env python
#
# Copyright 2007 Google Inc.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#

import sys
from antlr3 import *
from antlr3.compat import set, frozenset


HIDDEN = BaseRecognizer.HIDDEN

THIRD=13
SEPTEMBER=36
FOURTH=14
SECOND=12
WEDNESDAY=22
NOVEMBER=38
SATURDAY=25
JULY=34
APRIL=31
DIGITS=8
OCTOBER=37
MAY=32
EVERY=6
FEBRUARY=29
MONDAY=20
SUNDAY=26
DAY=19
JUNE=33
OF=4
MARCH=30
EOF=-1
JANUARY=28
MONTH=27
FRIDAY=24
MINUTES=18
FIFTH=15
TIME=5
SYNCHRONIZED=9
WS=41
QUARTER=40
THURSDAY=23
COMMA=10
DECEMBER=39
AUGUST=35
DIGIT=7
TUESDAY=21
HOURS=17
FOURTH_OR_FIFTH=16
FIRST=11


class GrocLexer(Lexer):

    grammarFileName = "Groc.g"
    antlr_version = version_str_to_tuple("3.1.1")
    antlr_version_str = "3.1.1"

    def __init__(self, input=None, state=None):
        if state is None:
            state = RecognizerSharedState()
        Lexer.__init__(self, input, state)

        self.dfa25 = self.DFA25(
            self, 25,
            eot = self.DFA25_eot,
            eof = self.DFA25_eof,
            min = self.DFA25_min,
            max = self.DFA25_max,
            accept = self.DFA25_accept,
            special = self.DFA25_special,
            transition = self.DFA25_transition
            )






    def mTIME(self, ):

        try:
            _type = TIME
            _channel = DEFAULT_CHANNEL

            pass
            alt1 = 4
            LA1 = self.input.LA(1)
            if LA1 == 48:
                LA1_1 = self.input.LA(2)

                if ((48 <= LA1_1 <= 57)) :
                    alt1 = 2
                elif (LA1_1 == 58) :
                    alt1 = 1
                else:
                    nvae = NoViableAltException("", 1, 1, self.input)

                    raise nvae

            elif LA1 == 49:
                LA1_2 = self.input.LA(2)

                if (LA1_2 == 58) :
                    alt1 = 1
                elif ((48 <= LA1_2 <= 57)) :
                    alt1 = 3
                else:
                    nvae = NoViableAltException("", 1, 2, self.input)

                    raise nvae

            elif LA1 == 50:
                LA1_3 = self.input.LA(2)

                if ((48 <= LA1_3 <= 51)) :
                    alt1 = 4
                elif (LA1_3 == 58) :
                    alt1 = 1
                else:
                    nvae = NoViableAltException("", 1, 3, self.input)

                    raise nvae

            elif LA1 == 51 or LA1 == 52 or LA1 == 53 or LA1 == 54 or LA1 == 55 or LA1 == 56 or LA1 == 57:
                alt1 = 1
            else:
                nvae = NoViableAltException("", 1, 0, self.input)

                raise nvae

            if alt1 == 1:
                pass
                self.mDIGIT()


            elif alt1 == 2:
                pass
                pass
                self.match(48)
                self.mDIGIT()





            elif alt1 == 3:
                pass
                pass
                self.match(49)
                self.mDIGIT()





            elif alt1 == 4:
                pass
                pass
                self.match(50)
                self.matchRange(48, 51)






            self.match(58)
            pass
            self.matchRange(48, 53)
            self.mDIGIT()






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mSYNCHRONIZED(self, ):

        try:
            _type = SYNCHRONIZED
            _channel = DEFAULT_CHANNEL

            pass
            self.match("synchronized")



            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mFIRST(self, ):

        try:
            _type = FIRST
            _channel = DEFAULT_CHANNEL

            pass
            alt2 = 2
            LA2_0 = self.input.LA(1)

            if (LA2_0 == 49) :
                alt2 = 1
            elif (LA2_0 == 102) :
                alt2 = 2
            else:
                nvae = NoViableAltException("", 2, 0, self.input)

                raise nvae

            if alt2 == 1:
                pass
                self.match("1st")


            elif alt2 == 2:
                pass
                self.match("first")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mSECOND(self, ):

        try:
            _type = SECOND
            _channel = DEFAULT_CHANNEL

            pass
            alt3 = 2
            LA3_0 = self.input.LA(1)

            if (LA3_0 == 50) :
                alt3 = 1
            elif (LA3_0 == 115) :
                alt3 = 2
            else:
                nvae = NoViableAltException("", 3, 0, self.input)

                raise nvae

            if alt3 == 1:
                pass
                self.match("2nd")


            elif alt3 == 2:
                pass
                self.match("second")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mTHIRD(self, ):

        try:
            _type = THIRD
            _channel = DEFAULT_CHANNEL

            pass
            alt4 = 2
            LA4_0 = self.input.LA(1)

            if (LA4_0 == 51) :
                alt4 = 1
            elif (LA4_0 == 116) :
                alt4 = 2
            else:
                nvae = NoViableAltException("", 4, 0, self.input)

                raise nvae

            if alt4 == 1:
                pass
                self.match("3rd")


            elif alt4 == 2:
                pass
                self.match("third")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mFOURTH(self, ):

        try:
            _type = FOURTH
            _channel = DEFAULT_CHANNEL

            pass
            pass
            self.match("4th")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mFIFTH(self, ):

        try:
            _type = FIFTH
            _channel = DEFAULT_CHANNEL

            pass
            pass
            self.match("5th")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mFOURTH_OR_FIFTH(self, ):

        try:
            _type = FOURTH_OR_FIFTH
            _channel = DEFAULT_CHANNEL

            pass
            alt5 = 2
            LA5_0 = self.input.LA(1)

            if (LA5_0 == 102) :
                LA5_1 = self.input.LA(2)

                if (LA5_1 == 111) :
                    alt5 = 1
                elif (LA5_1 == 105) :
                    alt5 = 2
                else:
                    nvae = NoViableAltException("", 5, 1, self.input)

                    raise nvae

            else:
                nvae = NoViableAltException("", 5, 0, self.input)

                raise nvae

            if alt5 == 1:
                pass
                pass
                self.match("fourth")
                _type = FOURTH;





            elif alt5 == 2:
                pass
                pass
                self.match("fifth")
                _type = FIFTH;









            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mDAY(self, ):

        try:
            _type = DAY
            _channel = DEFAULT_CHANNEL

            pass
            self.match("day")



            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mMONDAY(self, ):

        try:
            _type = MONDAY
            _channel = DEFAULT_CHANNEL

            pass
            self.match("mon")
            alt6 = 2
            LA6_0 = self.input.LA(1)

            if (LA6_0 == 100) :
                alt6 = 1
            if alt6 == 1:
                pass
                self.match("day")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mTUESDAY(self, ):

        try:
            _type = TUESDAY
            _channel = DEFAULT_CHANNEL

            pass
            self.match("tue")
            alt7 = 2
            LA7_0 = self.input.LA(1)

            if (LA7_0 == 115) :
                alt7 = 1
            if alt7 == 1:
                pass
                self.match("sday")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mWEDNESDAY(self, ):

        try:
            _type = WEDNESDAY
            _channel = DEFAULT_CHANNEL

            pass
            self.match("wed")
            alt8 = 2
            LA8_0 = self.input.LA(1)

            if (LA8_0 == 110) :
                alt8 = 1
            if alt8 == 1:
                pass
                self.match("nesday")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mTHURSDAY(self, ):

        try:
            _type = THURSDAY
            _channel = DEFAULT_CHANNEL

            pass
            self.match("thu")
            alt9 = 2
            LA9_0 = self.input.LA(1)

            if (LA9_0 == 114) :
                alt9 = 1
            if alt9 == 1:
                pass
                self.match("rsday")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mFRIDAY(self, ):

        try:
            _type = FRIDAY
            _channel = DEFAULT_CHANNEL

            pass
            self.match("fri")
            alt10 = 2
            LA10_0 = self.input.LA(1)

            if (LA10_0 == 100) :
                alt10 = 1
            if alt10 == 1:
                pass
                self.match("day")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mSATURDAY(self, ):

        try:
            _type = SATURDAY
            _channel = DEFAULT_CHANNEL

            pass
            self.match("sat")
            alt11 = 2
            LA11_0 = self.input.LA(1)

            if (LA11_0 == 117) :
                alt11 = 1
            if alt11 == 1:
                pass
                self.match("urday")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mSUNDAY(self, ):

        try:
            _type = SUNDAY
            _channel = DEFAULT_CHANNEL

            pass
            self.match("sun")
            alt12 = 2
            LA12_0 = self.input.LA(1)

            if (LA12_0 == 100) :
                alt12 = 1
            if alt12 == 1:
                pass
                self.match("day")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mJANUARY(self, ):

        try:
            _type = JANUARY
            _channel = DEFAULT_CHANNEL

            pass
            self.match("jan")
            alt13 = 2
            LA13_0 = self.input.LA(1)

            if (LA13_0 == 117) :
                alt13 = 1
            if alt13 == 1:
                pass
                self.match("uary")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mFEBRUARY(self, ):

        try:
            _type = FEBRUARY
            _channel = DEFAULT_CHANNEL

            pass
            self.match("feb")
            alt14 = 2
            LA14_0 = self.input.LA(1)

            if (LA14_0 == 114) :
                alt14 = 1
            if alt14 == 1:
                pass
                self.match("ruary")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mMARCH(self, ):

        try:
            _type = MARCH
            _channel = DEFAULT_CHANNEL

            pass
            self.match("mar")
            alt15 = 2
            LA15_0 = self.input.LA(1)

            if (LA15_0 == 99) :
                alt15 = 1
            if alt15 == 1:
                pass
                self.match("ch")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mAPRIL(self, ):

        try:
            _type = APRIL
            _channel = DEFAULT_CHANNEL

            pass
            self.match("apr")
            alt16 = 2
            LA16_0 = self.input.LA(1)

            if (LA16_0 == 105) :
                alt16 = 1
            if alt16 == 1:
                pass
                self.match("il")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mMAY(self, ):

        try:
            _type = MAY
            _channel = DEFAULT_CHANNEL

            pass
            self.match("may")



            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mJUNE(self, ):

        try:
            _type = JUNE
            _channel = DEFAULT_CHANNEL

            pass
            self.match("jun")
            alt17 = 2
            LA17_0 = self.input.LA(1)

            if (LA17_0 == 101) :
                alt17 = 1
            if alt17 == 1:
                pass
                self.match(101)






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mJULY(self, ):

        try:
            _type = JULY
            _channel = DEFAULT_CHANNEL

            pass
            self.match("jul")
            alt18 = 2
            LA18_0 = self.input.LA(1)

            if (LA18_0 == 121) :
                alt18 = 1
            if alt18 == 1:
                pass
                self.match(121)






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mAUGUST(self, ):

        try:
            _type = AUGUST
            _channel = DEFAULT_CHANNEL

            pass
            self.match("aug")
            alt19 = 2
            LA19_0 = self.input.LA(1)

            if (LA19_0 == 117) :
                alt19 = 1
            if alt19 == 1:
                pass
                self.match("ust")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mSEPTEMBER(self, ):

        try:
            _type = SEPTEMBER
            _channel = DEFAULT_CHANNEL

            pass
            self.match("sep")
            alt20 = 2
            LA20_0 = self.input.LA(1)

            if (LA20_0 == 116) :
                alt20 = 1
            if alt20 == 1:
                pass
                self.match("tember")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mOCTOBER(self, ):

        try:
            _type = OCTOBER
            _channel = DEFAULT_CHANNEL

            pass
            self.match("oct")
            alt21 = 2
            LA21_0 = self.input.LA(1)

            if (LA21_0 == 111) :
                alt21 = 1
            if alt21 == 1:
                pass
                self.match("ober")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mNOVEMBER(self, ):

        try:
            _type = NOVEMBER
            _channel = DEFAULT_CHANNEL

            pass
            self.match("nov")
            alt22 = 2
            LA22_0 = self.input.LA(1)

            if (LA22_0 == 101) :
                alt22 = 1
            if alt22 == 1:
                pass
                self.match("ember")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mDECEMBER(self, ):

        try:
            _type = DECEMBER
            _channel = DEFAULT_CHANNEL

            pass
            self.match("dec")
            alt23 = 2
            LA23_0 = self.input.LA(1)

            if (LA23_0 == 101) :
                alt23 = 1
            if alt23 == 1:
                pass
                self.match("ember")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mMONTH(self, ):

        try:
            _type = MONTH
            _channel = DEFAULT_CHANNEL

            pass
            pass
            self.match("month")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mQUARTER(self, ):

        try:
            _type = QUARTER
            _channel = DEFAULT_CHANNEL

            pass
            pass
            self.match("quarter")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mEVERY(self, ):

        try:
            _type = EVERY
            _channel = DEFAULT_CHANNEL

            pass
            pass
            self.match("every")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mHOURS(self, ):

        try:
            _type = HOURS
            _channel = DEFAULT_CHANNEL

            pass
            pass
            self.match("hours")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mMINUTES(self, ):

        try:
            _type = MINUTES
            _channel = DEFAULT_CHANNEL

            pass
            alt24 = 2
            LA24_0 = self.input.LA(1)

            if (LA24_0 == 109) :
                LA24_1 = self.input.LA(2)

                if (LA24_1 == 105) :
                    LA24_2 = self.input.LA(3)

                    if (LA24_2 == 110) :
                        LA24_3 = self.input.LA(4)

                        if (LA24_3 == 115) :
                            alt24 = 1
                        elif (LA24_3 == 117) :
                            alt24 = 2
                        else:
                            nvae = NoViableAltException("", 24, 3, self.input)

                            raise nvae

                    else:
                        nvae = NoViableAltException("", 24, 2, self.input)

                        raise nvae

                else:
                    nvae = NoViableAltException("", 24, 1, self.input)

                    raise nvae

            else:
                nvae = NoViableAltException("", 24, 0, self.input)

                raise nvae

            if alt24 == 1:
                pass
                self.match("mins")


            elif alt24 == 2:
                pass
                self.match("minutes")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mCOMMA(self, ):

        try:
            _type = COMMA
            _channel = DEFAULT_CHANNEL

            pass
            pass
            self.match(44)






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mOF(self, ):

        try:
            _type = OF
            _channel = DEFAULT_CHANNEL

            pass
            pass
            self.match("of")






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mWS(self, ):

        try:
            _type = WS
            _channel = DEFAULT_CHANNEL

            pass
            if (9 <= self.input.LA(1) <= 10) or self.input.LA(1) == 13 or self.input.LA(1) == 32:
                self.input.consume()
            else:
                mse = MismatchedSetException(None, self.input)
                self.recover(mse)
                raise mse

            _channel=HIDDEN;



            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mDIGIT(self, ):

        try:
            _type = DIGIT
            _channel = DEFAULT_CHANNEL

            pass
            pass
            self.matchRange(48, 57)






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mDIGITS(self, ):

        try:
            _type = DIGITS
            _channel = DEFAULT_CHANNEL

            pass
            pass
            self.mDIGIT()
            self.mDIGIT()






            self._state.type = _type
            self._state.channel = _channel

        finally:

            pass




    def mTokens(self):
        alt25 = 38
        alt25 = self.dfa25.predict(self.input)
        if alt25 == 1:
            pass
            self.mTIME()


        elif alt25 == 2:
            pass
            self.mSYNCHRONIZED()


        elif alt25 == 3:
            pass
            self.mFIRST()


        elif alt25 == 4:
            pass
            self.mSECOND()


        elif alt25 == 5:
            pass
            self.mTHIRD()


        elif alt25 == 6:
            pass
            self.mFOURTH()


        elif alt25 == 7:
            pass
            self.mFIFTH()


        elif alt25 == 8:
            pass
            self.mFOURTH_OR_FIFTH()


        elif alt25 == 9:
            pass
            self.mDAY()


        elif alt25 == 10:
            pass
            self.mMONDAY()


        elif alt25 == 11:
            pass
            self.mTUESDAY()


        elif alt25 == 12:
            pass
            self.mWEDNESDAY()


        elif alt25 == 13:
            pass
            self.mTHURSDAY()


        elif alt25 == 14:
            pass
            self.mFRIDAY()


        elif alt25 == 15:
            pass
            self.mSATURDAY()


        elif alt25 == 16:
            pass
            self.mSUNDAY()


        elif alt25 == 17:
            pass
            self.mJANUARY()


        elif alt25 == 18:
            pass
            self.mFEBRUARY()


        elif alt25 == 19:
            pass
            self.mMARCH()


        elif alt25 == 20:
            pass
            self.mAPRIL()


        elif alt25 == 21:
            pass
            self.mMAY()


        elif alt25 == 22:
            pass
            self.mJUNE()


        elif alt25 == 23:
            pass
            self.mJULY()


        elif alt25 == 24:
            pass
            self.mAUGUST()


        elif alt25 == 25:
            pass
            self.mSEPTEMBER()


        elif alt25 == 26:
            pass
            self.mOCTOBER()


        elif alt25 == 27:
            pass
            self.mNOVEMBER()


        elif alt25 == 28:
            pass
            self.mDECEMBER()


        elif alt25 == 29:
            pass
            self.mMONTH()


        elif alt25 == 30:
            pass
            self.mQUARTER()


        elif alt25 == 31:
            pass
            self.mEVERY()


        elif alt25 == 32:
            pass
            self.mHOURS()


        elif alt25 == 33:
            pass
            self.mMINUTES()


        elif alt25 == 34:
            pass
            self.mCOMMA()


        elif alt25 == 35:
            pass
            self.mOF()


        elif alt25 == 36:
            pass
            self.mWS()


        elif alt25 == 37:
            pass
            self.mDIGIT()


        elif alt25 == 38:
            pass
            self.mDIGITS()








    DFA25_eot = DFA.unpack(
        u"\1\uffff\4\27\2\uffff\1\27\1\uffff\2\27\15\uffff\1\36\2\uffff\2"
        u"\36\34\uffff\1\77\6\uffff"
        )

    DFA25_eof = DFA.unpack(
        u"\100\uffff"
        )

    DFA25_min = DFA.unpack(
        u"\1\11\4\60\1\141\1\145\1\60\1\150\2\60\2\141\1\uffff\1\141\1\160"
        u"\1\143\7\uffff\1\72\2\uffff\2\72\4\uffff\1\143\2\uffff\1\146\4"
        u"\uffff\1\151\4\uffff\1\156\1\162\2\uffff\1\154\6\uffff\1\164\6"
        u"\uffff"
        )

    DFA25_max = DFA.unpack(
        u"\1\167\1\72\1\163\1\156\1\162\1\171\1\162\1\164\1\165\1\164\1\72"
        u"\1\145\1\157\1\uffff\2\165\1\146\7\uffff\1\72\2\uffff\2\72\4\uffff"
        u"\1\160\2\uffff\1\162\4\uffff\1\165\4\uffff\1\156\1\171\2\uffff"
        u"\1\156\6\uffff\1\164\6\uffff"
        )

    DFA25_accept = DFA.unpack(
        u"\15\uffff\1\14\3\uffff\1\33\1\36\1\37\1\40\1\42\1\44\1\45\1\uffff"
        u"\1\1\1\3\2\uffff\1\4\1\46\1\5\1\2\1\uffff\1\17\1\20\1\uffff\1\10"
        u"\1\16\1\22\1\6\1\uffff\1\13\1\7\1\11\1\34\2\uffff\1\41\1\21\1\uffff"
        u"\1\24\1\30\1\32\1\43\1\31\1\15\1\uffff\1\23\1\25\1\26\1\27\1\35"
        u"\1\12"
        )

    DFA25_special = DFA.unpack(
        u"\100\uffff"
        )


    DFA25_transition = [
        DFA.unpack(u"\2\26\2\uffff\1\26\22\uffff\1\26\13\uffff\1\25\3\uffff"
        u"\1\1\1\2\1\3\1\4\1\7\1\11\4\12\47\uffff\1\17\2\uffff\1\13\1\23"
        u"\1\6\1\uffff\1\24\1\uffff\1\16\2\uffff\1\14\1\21\1\20\1\uffff\1"
        u"\22\1\uffff\1\5\1\10\2\uffff\1\15"),
        DFA.unpack(u"\12\30\1\31"),
        DFA.unpack(u"\12\33\1\31\70\uffff\1\32"),
        DFA.unpack(u"\4\34\6\36\1\31\63\uffff\1\35"),
        DFA.unpack(u"\12\36\1\31\67\uffff\1\37"),
        DFA.unpack(u"\1\42\3\uffff\1\41\17\uffff\1\43\3\uffff\1\40"),
        DFA.unpack(u"\1\47\3\uffff\1\44\5\uffff\1\45\2\uffff\1\46"),
        DFA.unpack(u"\12\36\1\31\71\uffff\1\50"),
        DFA.unpack(u"\1\51\14\uffff\1\52"),
        DFA.unpack(u"\12\36\1\31\71\uffff\1\53"),
        DFA.unpack(u"\12\36\1\31"),
        DFA.unpack(u"\1\54\3\uffff\1\55"),
        DFA.unpack(u"\1\57\7\uffff\1\60\5\uffff\1\56"),
        DFA.unpack(u""),
        DFA.unpack(u"\1\61\23\uffff\1\62"),
        DFA.unpack(u"\1\63\4\uffff\1\64"),
        DFA.unpack(u"\1\65\2\uffff\1\66"),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u"\1\31"),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u"\1\31"),
        DFA.unpack(u"\1\31"),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u"\1\35\14\uffff\1\67"),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u"\1\45\13\uffff\1\32"),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u"\1\37\13\uffff\1\70"),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u"\1\71"),
        DFA.unpack(u"\1\72\6\uffff\1\73"),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u"\1\75\1\uffff\1\74"),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u"\1\76"),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u""),
        DFA.unpack(u"")
    ]


    DFA25 = DFA




def main(argv, stdin=sys.stdin, stdout=sys.stdout, stderr=sys.stderr):
    from antlr3.main import LexerMain
    main = LexerMain(GrocLexer)
    main.stdin = stdin
    main.stdout = stdout
    main.stderr = stderr
    main.execute(argv)


if __name__ == '__main__':
    main(sys.argv)
