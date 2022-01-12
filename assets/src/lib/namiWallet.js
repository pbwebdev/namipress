import { cardano, NETWORK } from '../api/config'
import { hexToBech32, hexToBytes } from '../api/util'
import * as CSL from '@emurgo/cardano-serialization-lib-browser'

export const getNetwork = async (wallet) => {
    const id = await wallet.getNetworkId()

    return NETWORK[id]
}

export const getBalance = async () => {
    const balance = await cardano.getBalance()

    return CSL.Value.from_bytes(hexToBytes(balance)).coin().to_str()
}

export const getChangeAddress = async (wallet) => {
    const changeAddress = await wallet.getChangeAddress()

    return hexToBech32(changeAddress)
}

export const getUsedAddress = async (wallet) => {
    const usedAddresses = await wallet.getUsedAddresses()

    return usedAddresses.map((address) => hexToBech32(address))
}

export const getUnusedAddress = async (wallet) => {
    const unusedAddresses = await wallet.getUnusedAddresses()

    return unusedAddresses.map((address) => hexToBech32(address))
}

export const getRewardAddress = async (wallet) => {
    let rewardAddress

    if ('ccvault' === wallet.type) {
        rewardAddress = await wallet.getRewardAddresses()
        rewardAddress = rewardAddress[0]
    } else {
        rewardAddress = await wallet.getRewardAddress()
    }

    return hexToBech32(rewardAddress)
}

export const getUtxos = async () => {
    const rawUtxos = await cardano.getUtxos()

    return rawUtxos.map((utxo) => CSL.TransactionUnspentOutput.from_bytes(hexToBytes(utxo)))
}

export const getStakeKeyHash = async () => {
    const rewardAddress = await getRewardAddress()

    return CSL.RewardAddress.from_address(
        CSL.Address.from_bech32(rewardAddress)
    ).payment_cred().to_keyhash().to_bytes()
}

export const signAndSubmit = async (transaction) => {
    try {
        const witnesses = await cardano.signTx(hexToBytes(transaction.to_bytes()).toString('hex'))
        const signedTx = CSL.Transaction.new(
            transaction.body(),
            CSL.TransactionWitnessSet.from_bytes(hexToBytes(witnesses))
        )

        return await cardano.submitTx(hexToBytes(signedTx.to_bytes()).toString('hex'))
    } catch (error) {
        throw error.info
    }
}
