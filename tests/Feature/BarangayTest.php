<?php


// branch can create if data is validated, fuck it just do it and i can learn asi go, trust

use App\Models\Barangay;
use App\Models\User;

it('create a barangay with valid data and redirects with a sucess message', function () {
    $user = User::factory()->create();

    $res = $this->actingAs($user)->post(route('barangays.store'), [
        'code' => '1243',
        'name' => 'admin'
    ]);

    $res->assertRedirect(route('barangays.index'));
    $res->assertSessionHas('message', 'Barangay Created Successfully');

    $this->assertDatabaseHas('barangays', [
        'code' => '1243',
        'name' => 'admin'
    ]);
});




// BUG i create the Exist Barangay so checking if its missing is dumn becuase it exist to bigin with
it('reject a duplicate barangay name', function () {
    $user = User::factory()->create();
    Barangay::factory()->create(['name' => 'Exist Barangay']);
    $res = $this->actingAs($user)->post(route('barangays.store'), [
        'code' => '12432',
        'name' => 'Exist Barangay'
    ]);

    // if name unique  actually throws then code will throw one too, making another test just for it is uselss
    // -we will not do another test like one that will throw error message ebcuase we can just it below, but this is mroe for validation 
    // laravle handle the message shown
    $res->assertSessionHasErrors('name');
    $this->assertDatabaseCount('barangays', 1);


    // yup this is broken, fuck no ai just figure it out

});

it('allows updating a barangay witout changing its own name', function () {
    $user = User::factory()->create();
    $barangay = Barangay::factory()->create(['name' => 'Update Barangay']);
    $res = $this->actingAs($user)->put(route('barangays.update', $barangay), [
        'code' => 'udpate this',
        'name' => 'Update Barangay'
    ]);


    $res->assertSessionHasNoErrors();
    $res->assertRedirect(route('barangays.index'));
});

it('redirect a guest trying to create a barangay', function () {
    $res = $this->get(route('barangays.create'));
    $res->assertRedirect(route('login'));
});

it('rejects a code that exceeds the maximum allowed value', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('barangays.store'), [
        'name' => 'Test Barangay',
        'code' => 'Test Barangaydwasdf',
    ]);

    $response->assertSessionHasErrors('code');

    // we need things like assertDatabaseMissing on what we expect to be reject or fial to unsure that nothing slips pass anything, 
    // just a  little addition for precution
    $this->assertDatabaseMissing('barangays', ['name' => 'Test Barangay']);
});



it('prevents deleting a barangay that has user attached', function () {
    $barangay = Barangay::factory()->create();
    $user = User::factory()->create(['barangay_id' => $barangay->id]);


    $response = $this->actingAs($user)->delete(route('barangays.delete', $barangay));

    // $response->assertSessionHasErrors('ba');
    //  i dont need this becuase i dont really check in validation deos this barangay have relation


    // this is better, since we are tesint what controller give back, since controller already checks if barangay has relation to something
    // and it return a specific error when its true then all we ahve to do is look look for that error and confirm with assertDatabaseHas,to saeal the deal
    // but the problem with this is it can be bypass by changing error message to let it pass so easy bug workaround
    $response->assertSessionHas(
        'error',
       'Cannot delete this barangay because it has associated records'
    );

    
    $this->assertDatabaseHas('barangays', ['id' => $barangay->id]); // still exists
});

it('allows deleting a barangay with no user attached', function () {
    $user = User::factory()->create();
    $barangay = Barangay::factory()->create();

    $response = $this->actingAs($user)->delete(route('barangays.delete', $barangay));

    $response->assertRedirect(route('barangays.index'));
    $response->assertSessionHas('message', 'Barangay deleted');
    $this->assertDatabaseMissing('barangays', ['id' => $barangay->id]); // actually gone
});