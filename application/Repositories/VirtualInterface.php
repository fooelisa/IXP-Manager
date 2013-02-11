<?php

namespace Repositories;

use Doctrine\ORM\EntityRepository;

/**
 * VirtualInterface
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class VirtualInterface extends EntityRepository
{
    
    /**
     * Utility function to provide a count of different customer types as `type => count`
     * where type is as defined in Entities\Customer::$CUST_TYPES_TEXT
     *
     * @return array Number of customers of each customer type as `[type] => count`
     */
    public function getByLocation()
    {
        return $ints = $this->getEntityManager()->createQuery(
            "SELECT vi.id AS id, pi.speed AS speed, sw.infrastructure AS infrastructure, l.name AS locationname
                FROM Entities\\VirtualInterface vi
                    JOIN vi.Customer c
                    JOIN vi.PhysicalInterfaces pi
                    JOIN pi.SwitchPort sp
                    JOIN sp.Switcher sw
                    JOIN sw.Cabinet ca
                    JOIN ca.Location l
                WHERE
                    " . Customer::DQL_CUST_EXTERNAL
        )->getArrayResult();
    }


    /**
     * Utility function to provide an array of all virtual interfaces on a given
     * infrastructure (optionally with active VLAN Interfaces for a given protocol).
     *
     * Returns an array of:
     *
     *     * Customer ID (cid)
     *     * Customer Name (cname)
     *     * Customer Shortname (cshortname)
     *     * VirtualInterface ID (id)
     *     * Physical Interface ID (pid)
     *     * VLAN Interface ID (vlanid)
     *     * SwithPort ID (spid)
     *     * Switch ID (swid)
     *
     * @param int $infra The infrastructure to gather VirtualInterfaces for
     * @param int $proto Either 4 or 6 to limit the results to interface with IPv4 / IPv6
     * @param bool $externalOnly If true (default) then only external (non-internal) interfaces will be returned
     * @return array As defined above.
     * @throws \INEX_Exception
     */
    public function getForInfrastructure( $infra, $proto = false, $externalOnly = true )
    {
        $qstr = "SELECT c.id AS cid, c.name AS cname, c.shortname AS cshortname,
                       vi.id AS id, pi.id AS pid, vli.id AS vlanid, sp.id AS spid, sw.id as swid
                    FROM Entities\\VirtualInterface vi
                        JOIN vi.Customer c
                        JOIN vi.PhysicalInterfaces pi
                        JOIN vi.VlanInterfaces vli
                        JOIN pi.SwitchPort sp
                        JOIN sp.Switcher sw
                    WHERE
                        sw.infrastructure = ?1 ";
        
        if( $proto )
        {
            if( !in_array( $proto, [ 4, 6 ] ) )
                throw new \INEX_Exception( 'Invalid protocol specified' );
            
            $qstr .= "AND vli.ipv{$proto}enabled = 1 ";
        }
            
        if( $externalOnly )
            $qstr .= "AND " . Customer::DQL_CUST_EXTERNAL;
                    
        $qstr .= " ORDER BY c.name ASC";
        
        $q = $this->getEntityManager()->createQuery( $qstr );
        $q->setParameter( 1, $infra );
        return $q->getArrayResult();
    }
}
