<?php
/**
 * Policies Class
 *
 * @package TheyWorkForYou
 */

namespace MySociety\TheyWorkForYou;

/**
 * Policies
 *
 * Class to provide management (selection, sorting etc) of PublicWhip policies.
 */

class Policies {

    /**
     * Policy Positions
     *
     * Array of all policy positions available to use.
     *
     * Arrays are in the form `{id} => {text}`
     */

    protected $policies = array(
        363 => 'introducing <b>foundation hospitals</b>',
        810 => 'greater <b>regulation of gambling</b>',
        811 => '<b>smoking bans</b>',
        826 => 'equal <b>gay rights</b>',
        837 => 'a <strong>wholly elected</strong> House of Lords',
        975 => 'an <strong>investigation</strong> into the Iraq war',
        984 => 'replacing <b>Trident</b> with a new nuclear weapons system',
        996 => 'a <b>transparent Parliament</b>',
        1027 => 'a referendum on the UK\'s membership of the <b>EU</b>',
        1030 => 'measures to <b>prevent climate change</b>',
        1049 => 'the <b>Iraq war</b>',
        1050 => 'the <b>hunting ban</b>',
        1051 => 'introducing <b>ID cards</b>',
        1052 => 'university <b>tuition fees</b>',
        1053 => 'Labour\'s <b title="Including voting to maintain them">anti-terrorism laws</b>',
        1065 => 'more <b>EU integration</b>',
        1071 => 'allowing ministers to <b>intervene in inquests</b>',
        1074 => 'greater <b>autonomy for schools</b>',
        1079 => 'removing <b>hereditary peers</b> from the House of Lords',
        1084 => 'a more <a href="http://en.wikipedia.org/wiki/Proportional_representation">proportional system</a> for electing MPs',
        1087 => 'a <b>stricter asylum system</b>',
        1105 => 'the privatisation of <b>Royal Mail</b>',
        1109 => 'encouraging <b>occupational pensions</b>',
        1110 => 'increasing the <b>rate of VAT</b>',
        1113 => 'an <b>equal number of electors</b> per parliamentary constituency',
        1120 => 'capping <b>civil service redundancy payments</b>',
        1124 => 'automatic enrolment in <b>occupational pensions</b>',
        1132 => 'raising England&rsquo;s <b>undergraduate tuition fee</b> cap to &pound;9,000 per year',
        1136 => '<b>fewer MPs</b> in the House of Commons',
        6670 => 'a reduction in spending on <b>welfare benefits</b>',
        6671 => 'reducing central government <b>funding of local government</b>',
        6672 => 'reducing <b>housing benefit</b> for social tenants deemed to have excess bedrooms (which Labour describe as the "bedroom tax")',
        6673 => 'paying higher benefits over longer periods for those unable to work due to <b>illness or disability</b>',
        6674 => 'raising <b>welfare benefits</b> at least in line with prices',
        6676 => 'reforming the <b>NHS</b> so GPs buy services on behalf of their patients',
        6677 => 'restricting the provision of services to <b>private patients</b> by the NHS',
        6678 => 'greater restrictions on <b>campaigning by third parties</b>, such as charities, during elections',
        6679 => 'reducing the rate of <b>corporation tax</b>',
        6680 => 'raising the threshold at which people start to pay <b>income tax</b>',
        6681 => 'increasing the tax rate applied to <b>income over &pound;150,000</b>',
        6682 => 'ending <b>financial support</b> for some 16-19 year olds in training and further education',
        6683 => 'local councils keeping money raised from <b>taxes on business premises</b> in their areas',
        6684 => 'making local councils responsible for helping those in <b>financial need</b> afford their <b>council tax</b> and reducing the amount spent on such support',
        6685 => 'a <b>banker&rsquo;s bonus tax</b>',
        6686 => 'allowing <b>marriage</b> between two people of same sex',
        6687 => '<a href="http://en.wikipedia.org/wiki/Academy_(English_school)">academy schools</a>',
        6688 => 'use of <b>UK military forces</b> in combat operations overseas',
        6690 => 'measures to reduce <b>tax avoidance</b>',
        6691 => 'stronger tax <b>incentives for companies to invest</b> in assets',
        6692 => 'slowing the rise in <b>rail fares</b>',
        6693 => 'lower taxes on <b>fuel for motor vehicles</b>',
        6694 => 'higher taxes on <b>alcoholic drinks</b>',
        6695 => 'more <b>powers for local councils</b>',
        6696 => 'the introduction of elected <b>Police and Crime Commissioners</b>',
        6698 => '<b>fixed periods between parliamentary elections</b>',
        6699 => 'higher <b>taxes on plane tickets</b>',
        6697 => 'selling England&rsquo;s state owned <b>forests</b>',
        6702 => 'spending public money to create <b>guaranteed jobs for young people</b> who have spent a long time unemployed',
        6703 => 'laws to promote <b>equality and human rights</b>',
        6704 => 'financial incentives for <b>low carbon</b> emission <b>electricity generation</b> methods',
        6705 => 'requiring pub companies to offer <b>pub landlords rent-only leases</b>',
        6706 => 'strengthening the <b>Military Covenant</b>',
        6707 => 'restricting the scope of <b>legal aid</b>',
        6708 => 'transferring <b>more powers to the Welsh Assembly</b>',
        6709 => 'transferring <b>more powers to the Scottish Parliament</b>',
        6710 => '<b>culling badgers</b> to tackle bovine tuberculosis',
        6711 => 'an annual tax on the value of expensive homes (popularly known as a <b>mansion tax</b>)',
        6715 => 'allowing national security sensitive evidence to be put before <b>courts in secret sessions</b>',
        6716 => 'allowing employees to exchange some employment <b>rights for shares</b> in the company they work for',
        6718 => 'restrictions on <b>fees</b> charged to tenants by <b>letting agents</b>',
        6719 => 'limits on success <b>fees</b> paid to lawyers in <b>no-win no fee cases</b>',
        6720 => 'a statutory <b>register of lobbyists</b>',
        6721 => 'requiring the mass <b>retention of information about communications</b>',
    );

    /**
     * Policy Sets
     *
     * Collections of policies (by ID number) to be used for sorted and
     * restricted displays.
     */

    private $sets = array(
        'summary' => array(
            1113,
            1136,
            1132,
            1052,
            1109,
            1110,
            1027,
            1084,
            1065,
            6670,
            6673,
            6674,
            6678,
            984,
            837,
            1079,
            6671,
            6672,
        ),
        'social' => array(
            826,
            811,
            1050,
            6686,
            6703,
        ),
        'foreignpolicy' => array(
            6688,
            1049,
            975,
            984,
            1065,
            1027,
            6706,
        ),
        'welfare' => array(
            6672,
            6674,
            6673,
            6684,
            6670,
            6702,
        ),
        'taxation' => array(
            6680,
            1110,
            6694,
            6699,
            6693,
            6681,
            1109,
            1124,
            6685,
            6711,
            6716,
        ),
        'business' => array(
            6679,
            6690,
            6691
        ),
        'health' => array(
            6677,
            6676,
            363,
            811
        ),
        'education' => array(
            1074,
            1132,
            6687,
            6682,
            1052
        ),
        'reform' => array(
            6671,
            1113,
            1136,
            996,
            1084,
            837,
            6683,
            6678,
            6698,
            1079,
            6708,
            6709,
            6695,
        ),
        'home' => array(
            1087,
            1071,
            1051,
            6696,
            6721,
        ),
        'misc' => array(
            810,
            1030,
            6692,
            6697,
            1120,
            1053,
            1105,
            6704,
            6705,
            6707,
            6710,
            6715,
            6720,
            6719,
            6718,
        )
    );

    /**
     * Get Array
     *
     * Return an array of policies.
     *
     * @return array Array of policies in the form `[ {id} , {text} ]`
     */
    public function getArray() {
        $out = array();
        foreach ($this->policies as $policy_id => $policy_text)
        {
            $out[] = array(
                $policy_id,
                $policy_text
            );
        }
        return $out;
    }

    /**
     * Shuffle
     *
     * Shuffles the list of policy positions.
     *
     * @return self
     */

    public function shuffle() {

        $keys = array_keys($this->policies);
        shuffle($keys);
        $random = array();
        foreach ($keys as $key) {
            $random[$key] = $this->policies[$key];
        }

        $new_policies = new self();
        $new_policies->policies = $random;

        return $new_policies;
    }

    /**
     * Limit To Set
     *
     * Limit the policies to those in a set and order accordingly
     *
     * @param string $set The name of the set to use.
     *
     * @return self
     */
    public function limitToSet($set) {

        // Sanity check the set exists
        if (isset($this->sets[$set]))
        {
            $out = array();
            // Reassemble the new policies list based on the set.
            foreach ($this->sets[$set] as $set_policy)
            {
                if (isset($this->policies[$set_policy]))
                {
                    $out[$set_policy] = $this->policies[$set_policy];
                } else {
                    throw new \Exception ('Policy ' . $set_policy . ' in set "' . $set . '" does not exist.');
                }
            }

            $new_policies = new self();
            $new_policies->policies = $out;

            return $new_policies;

        } else {
            throw new \Exception ('Policy set "' . $set . '" does not exist.');
        }
    }

}
