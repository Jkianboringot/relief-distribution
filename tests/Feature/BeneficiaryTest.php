<?php


// 1.beneficary cannot exist for more than one barangay, it can only be one, and the way we will ensure is having duplicate system not needed but will do if bored, that dup system pretty much take fullname,dob,number, and compare it will be givin a point the more the point the more duplicated it is,
//     -recap: beneficiary can only belong to one barangay

    // scan:{7890-=2324}
    // ->if case true, bene status is claimed na -> itawasn error message or warning
    // ->if case false,scan -> route -> controller -> service->stock:{7890-=2324}->bene->claimed->mabawas sa stock ning baranay

// we need to get started fuck