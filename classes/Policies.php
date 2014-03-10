<?php

namespace MySociety\TheyWorkForYou;

/**
 * Policies
 */

class Policies {

    /* *
     * Policy Positions
     *
     * Array of policy positions available to use.
     *
     * Arrays are in the form PublicWhip ID, display string, and if the policy
     * is MP only.
     */

    public $policies = array(
        array(363, 'introducing <b>foundation hospitals</b>'),
        array(811, 'a <b>smoking ban</b>', true),
        array(826, 'equal <b>gay rights</b>'),
        array(984, 'replacing <b>Trident</b> with a new nuclear weapons system'),
        array(996, 'a <b>transparent Parliament</b>'),
        array(1027, 'a referendum on the UK\'s membership of the <b>EU</b>'),
        array(1030, 'laws to <b>stop climate change</b>'),
        array(1049, 'the <b>Iraq war</b>'),
        array(1050, 'the <b>hunting ban</b>'),
        array(1051, 'introducing <b>ID cards</b>'),
        array(1052, 'university <b>tuition fees</b>'),
        array(1053, 'Labour\'s <b title="Including voting to maintain them">anti-terrorism laws</b>', true),
        array(1065, 'more <b>EU integration</b>'),
        array(1071, 'allowing ministers to <b>intervene in inquests</b>'),
        array(1074, 'greater <b>autonomy for schools</b>'),
        array(1079, 'removing <b>hereditary peers</b> from the House of Lords'),
        array(1084, 'a more <a href="http://en.wikipedia.org/wiki/Proportional_representation">proportional system</a> for electing MPs'),
        array(1087, 'a <b>stricter asylum system</b>'),
        array(1110, 'increasing the <b>rate of VAT</b>'),
        array(1113, 'an <b>equal number of electors</b> per parliamentary constituency'),
        array(1124, 'automatic enrolment in <b>occupational pensions</b>'),
        array(1136, '<b>fewer MPs</b> in the House of Commons'),
        array(6670, 'a reduction in spending on <b>welfare benefits</b>'),
        array(6671, 'reducing central government <b>funding of local government</b>'),
        array(6672, 'reducing <b>housing benefit</b> for social tenants deemed to have excess bedrooms (which Labour describe as the "bedroom tax")'),
        array(6673, 'paying higher benefits over longer periods for those unable to work due to <b>illness or disability</b>'),
        array(6674, 'raising <b>welfare benefits</b> at least in line with prices'),
        array(6676, 'reforming the <b>NHS</b> so GPs buy services on behalf of their patients'),
        array(6677, 'restricting the provision of services to <b>private patients</b> by the NHS'),
        array(6678, 'greater restrictions on <b>campaigning by third parties</b>, such as charities, during elections'),
        array(6679, 'reducing the rate of <b>corporation tax</b>'),
        array(6680, 'raising the threshold at which people start to pay <b>income tax</b>'),
        array(6681, 'increasing the tax rate applied to <b>income over £150,000</b>'),
        array(6686, 'allowing <b>marriage</b> between two people of same sex'),
        array(6687, '<a href="http://en.wikipedia.org/wiki/Academy_(English_school)">academy schools</a>'),
        array(6688, 'use of <b>UK military forces</b> in combat operations overseas'),
        array(6690, 'measures to reduce <b>tax avoidance</b>'),
        array(6691, 'stronger tax <b>incentives for companies to invest</b> in assets'),
        array(6694, 'higher taxes on <b>alcoholic drinks</b>'),
        array(6696, 'the introduction of elected <b>Police and Crime Commissioners</b>'),
        array(6697, 'selling England&rsquo;s state owned <b>forests</b>'),
    );

    /**
     * Joined Positions
     */

    public $joined = array(
        1079 => array(837, 'a <strong>wholly elected</strong> House of Lords'),
        1049 => array(975, 'an <strong>investigation</strong> into the Iraq war'),
        1052 => array(1132, 'raising England&rsquo;s undergraduate tuition fee cap to &pound;9,000 per year'),
        1124 => array(1109, 'encouraging occupational pensions'),
    );

    /**
     * Shuffle
     *
     * Shuffles the list of policy positions.
     */

    public function shuffle() {
        shuffle($this->policies);
        return $this;
    }

}
